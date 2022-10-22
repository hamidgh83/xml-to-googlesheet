<?php

namespace App\Service\Google;

use Google\Exception as GoogleException;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\ValueRange;
use Psr\Log\LoggerInterface;

class SpreadsheetClient implements SpreadsheetInterface
{
    private Spreadsheet $spreadsheet;

    private array $sheets;

    public function __construct(
        protected GoogleSheets $googleSheetsService,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Loads a spreadsheet by ID.
     */
    public function loadSpreadsheet(string $spreadsheetId): self
    {
        $this->spreadsheet = $this->googleSheetsService->spreadsheets->get($spreadsheetId);
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

        try {
            $body = new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    'addSheet' => ['properties' => ['title' => $name]],
                ],
            ]);

            $this->googleSheetsService
                ->spreadsheets
                ->batchUpdate($this->getSpreadsheet()->spreadsheetId, $body)
            ;

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

        $result = $this->googleSheetsService
            ->spreadsheets_values
            ->batchUpdate($this->getSpreadsheet()
            ->spreadsheetId, $body)
        ;

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
