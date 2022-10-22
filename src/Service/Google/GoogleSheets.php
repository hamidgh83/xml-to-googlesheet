<?php

namespace App\Service\Google;

use Google\Service\Sheets;
use Google_Client;

class GoogleSheets extends Sheets
{
    public function __construct(Google_Client $client)
    {
        $client->setScopes([GoogleSheets::SPREADSHEETS, GoogleSheets::DRIVE]);
        parent::__construct($client);
    }
}
