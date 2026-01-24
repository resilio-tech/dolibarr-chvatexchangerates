<?php
/**
 * Standalone unit tests for ExchangeRateParser
 * No Dolibarr dependency required
 *
 * Run with: phpunit htdocs/custom/chvatexchangerates/test/phpunit/unit/ExchangeRateParserTest.php
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__).'/../../../class/ExchangeRateParser.class.php';

class ExchangeRateParserTest extends TestCase
{
	/**
	 * @var ExchangeRateParser
	 */
	private $parser;

	protected function setUp(): void
	{
		$this->parser = new ExchangeRateParser();
	}

	// ========================================
	// Tests for parseXml() - Valid XML
	// ========================================

	public function testParseXmlValidSingleCurrency()
	{
		$xml = $this->getValidXmlSingleCurrency();

		$result = $this->parser->parseXml($xml);

		$this->assertTrue($result);
		$this->assertTrue($this->parser->isValid());
		$this->assertEmpty($this->parser->getErrors());
	}

	public function testParseXmlValidMultipleCurrencies()
	{
		$xml = $this->getValidXmlMultipleCurrencies();

		$result = $this->parser->parseXml($xml);

		$this->assertTrue($result);
		$rates = $this->parser->getAllRates();
		$this->assertCount(3, $rates);
	}

	public function testParseXmlExtractsDate()
	{
		$xml = $this->getValidXmlSingleCurrency();

		$this->parser->parseXml($xml);

		$this->assertEquals('2024-01-15', $this->parser->getDate());
	}

	public function testParseXmlExtractsCurrencyCode()
	{
		$xml = $this->getValidXmlSingleCurrency();

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		$this->assertEquals('EUR', $rates[0]['code']);
	}

	public function testParseXmlCalculatesInverseRate()
	{
		$xml = $this->getValidXmlSingleCurrency();

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		// Original rate: 0.95 CHF per 1 EUR
		// Inverse rate: 1/0.95 = 1.0526... EUR per 1 CHF
		$this->assertEquals(0.95, $rates[0]['original_rate']);
		$this->assertEqualsWithDelta(1.0526, $rates[0]['rate'], 0.001);
	}

	// ========================================
	// Tests for parseXml() - Invalid XML
	// ========================================

	public function testParseXmlEmptyString()
	{
		$result = $this->parser->parseXml('');

		$this->assertFalse($result);
		$this->assertFalse($this->parser->isValid());
		$this->assertContains('EmptyXmlString', $this->parser->getErrors());
	}

	public function testParseXmlInvalidXml()
	{
		$result = $this->parser->parseXml('not valid xml');

		$this->assertFalse($result);
		$this->assertFalse($this->parser->isValid());
	}

	public function testParseXmlMalformedXml()
	{
		$xml = '<root><unclosed>';

		$result = $this->parser->parseXml($xml);

		$this->assertFalse($result);
	}

	// ========================================
	// Tests for getRateForCurrency()
	// ========================================

	public function testGetRateForCurrencyFound()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rate = $this->parser->getRateForCurrency('EUR');

		$this->assertNotNull($rate);
		$this->assertEquals('EUR', $rate['code']);
	}

	public function testGetRateForCurrencyCaseInsensitive()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rate = $this->parser->getRateForCurrency('eur');

		$this->assertNotNull($rate);
		$this->assertEquals('EUR', $rate['code']);
	}

	public function testGetRateForCurrencyNotFound()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rate = $this->parser->getRateForCurrency('XYZ');

		$this->assertNull($rate);
	}

	// ========================================
	// Tests for getRatesForCurrencies()
	// ========================================

	public function testGetRatesForCurrenciesMultiple()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rates = $this->parser->getRatesForCurrencies(array('EUR', 'USD'));

		$this->assertCount(2, $rates);
	}

	public function testGetRatesForCurrenciesCaseInsensitive()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rates = $this->parser->getRatesForCurrencies(array('eur', 'usd'));

		$this->assertCount(2, $rates);
	}

	public function testGetRatesForCurrenciesPartialMatch()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rates = $this->parser->getRatesForCurrencies(array('EUR', 'XYZ'));

		$this->assertCount(1, $rates);
		$this->assertEquals('EUR', $rates[0]['code']);
	}

	public function testGetRatesForCurrenciesNoMatch()
	{
		$xml = $this->getValidXmlMultipleCurrencies();
		$this->parser->parseXml($xml);

		$rates = $this->parser->getRatesForCurrencies(array('XYZ', 'ABC'));

		$this->assertEmpty($rates);
	}

	// ========================================
	// Tests for getDateTimestamp()
	// ========================================

	public function testGetDateTimestamp()
	{
		$xml = $this->getValidXmlSingleCurrency();
		$this->parser->parseXml($xml);

		$timestamp = $this->parser->getDateTimestamp();

		$this->assertIsInt($timestamp);
		$this->assertEquals('2024-01-15', date('Y-m-d', $timestamp));
	}

	public function testGetDateTimestampNoDate()
	{
		$xml = '<root></root>';
		$this->parser->parseXml($xml);

		$timestamp = $this->parser->getDateTimestamp();

		$this->assertFalse($timestamp);
	}

	// ========================================
	// Tests for edge cases
	// ========================================

	public function testParseXmlSkipsZeroRate()
	{
		$xml = '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise code="eur">
					<kurs>0</kurs>
				</devise>
			</root>';

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		$this->assertEmpty($rates);
	}

	public function testParseXmlSkipsNegativeRate()
	{
		$xml = '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise code="eur">
					<kurs>-0.95</kurs>
				</devise>
			</root>';

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		$this->assertEmpty($rates);
	}

	public function testParseXmlSkipsMissingCode()
	{
		$xml = '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise>
					<kurs>0.95</kurs>
				</devise>
			</root>';

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		$this->assertEmpty($rates);
	}

	public function testParseXmlNormalizesCodeToUppercase()
	{
		$xml = '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise code="eur">
					<kurs>0.95</kurs>
				</devise>
			</root>';

		$this->parser->parseXml($xml);

		$rates = $this->parser->getAllRates();
		$this->assertEquals('EUR', $rates[0]['code']);
	}

	// ========================================
	// Helper methods
	// ========================================

	private function getValidXmlSingleCurrency(): string
	{
		return '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise code="eur">
					<kurs>0.95</kurs>
				</devise>
			</root>';
	}

	private function getValidXmlMultipleCurrencies(): string
	{
		return '<?xml version="1.0"?>
			<root>
				<datum>2024-01-15</datum>
				<devise code="eur">
					<kurs>0.95</kurs>
				</devise>
				<devise code="usd">
					<kurs>0.88</kurs>
				</devise>
				<devise code="gbp">
					<kurs>1.12</kurs>
				</devise>
			</root>';
	}
}
