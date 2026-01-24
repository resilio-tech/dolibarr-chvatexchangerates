<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       class/ExchangeRateParser.class.php
 * \ingroup    chtvaexchangerates
 * \brief      Class for parsing exchange rate data from Swiss customs API XML
 */

/**
 * Class ExchangeRateParser
 *
 * Parses exchange rate data from the Swiss customs (BAZG) API XML format
 * This class has no dependency on Dolibarr and can be unit tested independently
 */
class ExchangeRateParser
{
	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var string The date of the exchange rates
	 */
	public $date;

	/**
	 * @var array Parsed exchange rates
	 */
	public $rates = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Parse XML string from Swiss customs API
	 *
	 * @param string $xmlString XML content from the API
	 * @return bool True if parsing successful, false otherwise
	 */
	public function parseXml(string $xmlString): bool
	{
		$this->errors = array();
		$this->rates = array();
		$this->date = null;

		if (empty($xmlString)) {
			$this->errors[] = 'EmptyXmlString';
			return false;
		}

		// Suppress warnings and use error handling
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xmlString);

		if ($xml === false) {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$this->errors[] = 'XmlParseError: ' . trim($error->message);
			}
			libxml_clear_errors();
			return false;
		}

		// Parse date
		if (isset($xml->datum)) {
			$this->date = (string) $xml->datum;
		}

		// Parse each currency
		if (isset($xml->devise)) {
			foreach ($xml->devise as $devise) {
				$rate = $this->parseDevise($devise);
				if ($rate !== null) {
					$this->rates[] = $rate;
				}
			}
		}

		return true;
	}

	/**
	 * Parse a single devise (currency) element
	 *
	 * @param SimpleXMLElement $devise The devise XML element
	 * @return array|null Parsed rate data or null if invalid
	 */
	protected function parseDevise($devise): ?array
	{
		$code = isset($devise['code']) ? strtoupper((string) $devise['code']) : null;
		$kurs = isset($devise->kurs) ? (string) $devise->kurs : null;

		if (empty($code) || $kurs === null) {
			return null;
		}

		// Parse kurs (rate) - the API provides CHF per foreign currency
		// We need to convert to foreign currency per CHF
		$kursFloat = floatval($kurs);
		if ($kursFloat <= 0) {
			return null;
		}

		return array(
			'code' => $code,
			'original_rate' => $kursFloat,  // CHF per 1 foreign currency
			'rate' => 1 / $kursFloat,       // Foreign currency per 1 CHF
		);
	}

	/**
	 * Get rates filtered by currency codes
	 *
	 * @param array $currencyCodes Array of currency codes to filter (lowercase or uppercase)
	 * @return array Filtered rates
	 */
	public function getRatesForCurrencies(array $currencyCodes): array
	{
		$normalizedCodes = array_map('strtolower', $currencyCodes);
		$filtered = array();

		foreach ($this->rates as $rate) {
			if (in_array(strtolower($rate['code']), $normalizedCodes)) {
				$filtered[] = $rate;
			}
		}

		return $filtered;
	}

	/**
	 * Get rate for a specific currency
	 *
	 * @param string $currencyCode Currency code (e.g., 'EUR', 'USD')
	 * @return array|null Rate data or null if not found
	 */
	public function getRateForCurrency(string $currencyCode): ?array
	{
		$normalizedCode = strtolower($currencyCode);

		foreach ($this->rates as $rate) {
			if (strtolower($rate['code']) === $normalizedCode) {
				return $rate;
			}
		}

		return null;
	}

	/**
	 * Get all parsed rates
	 *
	 * @return array All parsed rates
	 */
	public function getAllRates(): array
	{
		return $this->rates;
	}

	/**
	 * Get the date of the exchange rates
	 *
	 * @return string|null Date string or null if not available
	 */
	public function getDate(): ?string
	{
		return $this->date;
	}

	/**
	 * Get the date as a Unix timestamp
	 *
	 * @return int|false Unix timestamp or false if date not available or invalid
	 */
	public function getDateTimestamp()
	{
		if (empty($this->date)) {
			return false;
		}

		return strtotime($this->date);
	}

	/**
	 * Check if parsing was successful
	 *
	 * @return bool True if no errors
	 */
	public function isValid(): bool
	{
		return empty($this->errors);
	}

	/**
	 * Get all error messages
	 *
	 * @return array Array of error messages
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
