<?php

namespace App\Service\XML;

use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class Reader
{
    /**
     * Symfony Crawler.
     */
    protected Crawler $crawler;

    /**
     * XML parsed items.
     */
    private array $items = [];

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

        $this->crawler = new Crawler();
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
