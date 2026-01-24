# CHTVAEXCHANGERATES FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

Made with love from Switzerland, by [Resilio Ltd.](https://resilio-solutions.com)

**Requires Multicurrency module to be activated**.

## Description

This module automatically fetches official exchange rates for VAT purposes in Switzerland from the Swiss Federal Office for Customs and Border Security (BAZG) API.

The exchange rates are synchronized daily via a cron job and stored in Dolibarr's multicurrency system.

## Requirements

- Dolibarr 11.0 or higher
- PHP 7.4 or higher
- Multicurrency module enabled
- cURL extension enabled

## Installation

1. Download the latest release from the [releases page](../../releases)
2. Extract the module to `htdocs/custom/chvatexchangerates`
3. Enable the module in Dolibarr: Home > Setup > Modules > Interfaces
4. Configure the module in Home > Setup > Modules > CHTVAExchangeRates

## Configuration

1. Go to Home > Setup > Modules > CHTVAExchangeRates
2. For Dolibarr v20+: Select a user for currency rate creation
3. The cron job will be automatically created when the module is enabled

## Features

- Automatic daily synchronization of exchange rates
- Support for all currencies available in the Swiss customs API
- Compatible with Dolibarr's multicurrency system
- No manual intervention required after setup

## Cron Job

The module creates a cron job that runs daily to synchronize exchange rates. You can also trigger it manually:

1. Go to Home > Admin tools > Scheduled jobs
2. Find the "Sync ExchangeRate" job
3. Click "Execute now" to run it manually

## Data Source

Exchange rates are fetched from:
`https://www.backend-rates.bazg.admin.ch/api/xmldaily`

This is the official API of the Swiss Federal Office for Customs and Border Security (BAZG).

## Translations

You are welcome to contribute to translations, which can be completed manually by editing files in the `langs` directories.

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
