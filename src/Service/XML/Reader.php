<?php

namespace App\Service\XML;

use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class Reader
{
    /**
     * XML parsed items.
     */
    private array $items = [];

    public function __construct(
        protected Crawler $crawler,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Load a given XML file.
     *
     * @param string $xmlFile The file path
     *
     * @throws RuntimeException exception on failure
     */
    public function load(string $xmlFile): self
    {
        if (!$xmlContent = file_get_contents($xmlFile)) {
            $this->logger->error(sprintf('Cannot read content from %s', $xmlFile));

            throw new RuntimeException('Cannot load the file content. Please check your file path and try again.');
        }

        $this->crawler->addXmlContent($xmlContent);
        $this->crawler = $this->crawler->filterXPath('//catalog/item');

        return $this;
    }

    /**
     * Chuncks the data and reads a new chunck on every call.
     */
    public function read(int $length = null): self|bool
    {
        static $offset = 0;
        $iterableItems = $this->crawler->slice($offset, $length);

        if ($iterableItems->count() < 1) {
            return false;
        }

        $this->resetItems();
        foreach ($iterableItems as $item) {
            $rows = [];
            foreach ($item->childNodes as $dom) {
                $rows[$dom->nodeName] = $dom->nodeValue;
            }
            $this->addItem($rows);
        }

        $offset += $length;

        return $this;
    }

    /**
     * Count parsed items.
     */
    public function count()
    {
        return count($this->getItems());
    }

    /**
     * Calls an anonymous function on each records of the parsed XML data.
     */
    public function each(Closure $callback): array
    {
        $results = [];
        foreach ($this->getItems() as $item) {
            $results[] = $callback($item);
        }

        return $results;
    }

    /**
     * Get items keys.
     */
    public function getHeaders(): array
    {
        $iterableItems = $this->crawler->first();

        if ($iterableItems->count() < 1) {
            return false;
        }

        $headers = [];
        foreach ($iterableItems as $item) {
            foreach ($item->childNodes as $dom) {
                $headers[] = $dom->nodeName;
            }
        }

        return $headers;
    }

    /**
     * Add a parsed item node.
     */
    private function addItem(array $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Get parsed items.
     */
    private function getItems(): array
    {
        return $this->items;
    }

    /**
     * Reset parsed items.
     */
    private function resetItems(): self
    {
        $this->items = [];

        return $this;
    }
}
