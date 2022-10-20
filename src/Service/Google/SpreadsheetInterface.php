<?php

namespace App\Service\Google;

interface SpreadsheetInterface
{
    /** Default rows limit by google sheet */
    public const ROWS_LIMIT = 1000;

    public function loadSpreadsheet(string $spreadsheetId);

    public function addSheet(string $prefix): ?string;

    public function update(string $sheet, array $values): int;
}
