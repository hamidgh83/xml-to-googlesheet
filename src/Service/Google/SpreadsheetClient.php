<?php

namespace App\Service\Google;

use Google\Exception as GoogleException;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\ValueRange;
use Google_Client;
use Psr\Log\LoggerInterface;

class SpreadsheetClient implements SpreadsheetInterface
{
    protected Google_Client $client;

    protected LoggerInterface $logger;

    private Spreadsheet $spreadsheet;

    private array $sheets;

    public function __construct(Google_Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->client->setScopes([GoogleSheets::SPREADSHEETS, GoogleSheets::DRIVE]);
    }

    /**
     * Loads a spreadsheet by ID.
     */
    public function loadSpreadsheet(string $spreadsheetId): self
    {
        $service = new GoogleSheets($this->client);

        $this->spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets            = $this->spreadsheet->getSheets();
        foreach ($sheets as $sheet) {
            $this->sheets[] = $sheet->properties->title;
        }

        return $this;
    }

    /**
     * Returns already loaded spreadsheet.
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * Creates a new sheet and returns its title.
     */
    public function addSheet(string $name): bool
    {
        if ($this->hasSheet($name)) {
            return true;
        }

        $service = new GoogleSheets($this->client);

        try {
            $body = new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    'addSheet' => ['properties' => ['title' => $name]],
                ],
            ]);

            $service->spreadsheets->batchUpdate($this->getSpreadsheet()->spreadsheetId, $body);

            return true;
        } catch (GoogleException $e) {
            $this->logger->error(sprintf(
                'Cannot create a new sheet titled %s. Reason: $s',
                $name,
                $e->getMessage()
            ));
        }

        return false;
    }

    /**
     * Updates a spreadsheet and returns number of affected rows.
     *
     * @param string $sheet  Sheet name
     * @param string $values Values to be stored
     */
    public function update(string $sheet, array $values): int
    {
        $data   = [];
        $data[] = new ValueRange([
            'range'  => $sheet,
            'values' => $values,
        ]);

        $body = new BatchUpdateValuesRequest([
            'valueInputOption' => 'USER_ENTERED',   // Valid options: INPUT_VALUE_OPTION_UNSPECIFIED, RAW or USER_ENTERED
            'data'             => $data,
        ]);

        $service = new GoogleSheets($this->client);
        $result  = $service->spreadsheets_values->batchUpdate($this->getSpreadsheet()->spreadsheetId, $body);

        return $result->getTotalUpdatedRows();
    }

    /**
     * Detemines if there is a sheet with the same title.
     */
    private function hasSheet(string $title): bool
    {
        return in_array($title, $this->sheets);
    }
}
