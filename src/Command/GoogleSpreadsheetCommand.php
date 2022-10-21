<?php

namespace App\Command;

use App\Service\Google\SpreadsheetClient;
use App\Service\Google\SpreadsheetInterface;
use App\Service\XML\Reader as XMLReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'xml:google:spreadsheet',
    description: 'Parse an XML file and push to the Google Spreadsheet.',
)]
class GoogleSpreadsheetCommand extends Command
{
    protected XMLReader $xmlReaderService;

    protected SpreadsheetClient $spreadsheetClient;

    public function __construct(XMLReader $xmlReaderService, SpreadsheetClient $spreadsheetClient)
    {
        parent::__construct();
        $this->xmlReaderService  = $xmlReaderService;
        $this->spreadsheetClient = $spreadsheetClient;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Spreadsheet ID')
            ->addOption('input', 'i', InputOption::VALUE_OPTIONAL, 'The XML file path')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $xmlFile = $input->getOption('input') ?? __DIR__ . '/../../data/coffee_feed.xml';

        try {
            $this->spreadsheetClient->loadSpreadsheet($input->getArgument('id'));

            $crawler   = $this->xmlReaderService->load($xmlFile);
            $headers[] = $crawler->getHeaders();

            list($sheetName, $total) = $this->newSheet($headers);

            $chunkSize = SpreadsheetInterface::ROWS_LIMIT - $total;
            while ($items = $crawler->read($chunkSize)) {
                $values = $items->each(function ($item) {
                    return array_values($item);
                });

                $total += $this->spreadsheetClient->update($sheetName . '!A2', $values);
                printf('%d rows updated at %s.' . PHP_EOL, $total, $sheetName);

                if ($items->count() < $chunkSize) {
                    break;
                }

                if (SpreadsheetInterface::ROWS_LIMIT == $total) {
                    list($sheetName, $total) = $this->newSheet($headers);
                }
            }
        } catch (\Throwable $th) {
            $io->error($th->getMessage());

            return Command::FAILURE;
        }

        $io->success('Your XML data was transfered successfully.');

        return Command::SUCCESS;
    }

    /**
     * Creates a new sheet and adds headers.
     *
     * @param array  $headers Header values
     * @param string $prefix  Sheet name prefix
     *
     * @return array Returns an array of sheet name and number of affected rows by header values
     *
     * @throws Exception Throws exception in case of failure
     */
    private function newSheet(array $headers, string $prefix = 'Sheet'): array
    {
        static $index = 0;

        $sheetName = $prefix . ++$index;
        if ($this->spreadsheetClient->addSheet($sheetName)) {
            $count = $this->spreadsheetClient->update($sheetName . '!A1', $headers);

            return [$sheetName, $count];
        }

        throw new \Exception('Cannot create new sheet.');
    }
}
