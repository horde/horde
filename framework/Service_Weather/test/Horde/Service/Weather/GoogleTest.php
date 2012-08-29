<?php
/**
 * Horde_Service_Weather tests
 *
 * PHP Version 5
 *
 * @category Horde
 * @package Service_Weather
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @link       http://pear.horde.org/index.php?package=Service_Weather
 */

require_once __DIR__ . '/Autoload.php';

class Horde_Service_Weather_GoogleTest extends PHPUnit_Framework_TestCase
{

    private $_unsupported = array(
        'pressure', 'pressure_trend', 'logo_url', 'dewpoint', 'wind_direction',
        'wind_degrees', 'wind_speed', 'wind_gust', 'visibility', 'heat_index',
        'wind_chill');

    public function testCurrentConditionsEnglish()
    {
        $weather = $this->_getStub('boston_google.xml');

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('boston,ma');

        // Condition
        $this->assertEquals('Mostly Cloudy', $conditions->condition);

        // Humidity
        $this->assertEquals('Humidity: 68%', $conditions->humidity);

        // Temp (F)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(50, $conditions->temp);

        // Temp (C)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(10, $conditions->temp);

        // Wind
        $this->assertEquals('Wind: N at 0 mph', $conditions->wind);

        // Test unsupported properties
        foreach ($this->_unsupported as $property) {
            $this->assertNull($conditions->$property);
        }
    }

    public function testCurrentConditionsGerman()
    {
        date_default_timezone_set('America/New_York');
        $weather = $this->_getStub('boston_google_de.xml');

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('boston,ma');

        // Condition
        $this->assertEquals('Meistens bewölkt', $conditions->condition);

        // Humidity
        $this->assertEquals('Luftfeuchtigkeit: 68 %', $conditions->humidity);

        // Temp (F)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(50, $conditions->temp);

        // Temp (C)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(10, $conditions->temp);

        // Wind
        $this->assertEquals('Wind: N mit 0 km/h', $conditions->wind);

        // Time
        $this->assertEquals('2011-11-26 21:54:00', (string)$conditions->time);

        // Test unsupported properties
        foreach ($this->_unsupported as $property) {
            $this->assertNull($conditions->$property);
        }
    }

    public function testGetStation()
    {
        $weather = $this->_getStub('boston_google.xml');
        $weather->getCurrentConditions('boston,ma');
        $station = $weather->getStation();
        $this->assertEquals('Boston, MA', $station->name);
    }

    public function testForecast()
    {
        date_default_timezone_set('America/New_York');
        $weather = $this->_getStub('boston_google.xml');
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $forecast = $weather->getForecast('boston,ma');
        $this->assertEquals('2011-11-26 21:54:00', (string)$forecast->getForecastTime());

        $dayOne = $forecast->getForecastDay(0);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Base', $dayOne);
        $this->assertEquals('Mostly Sunny', $dayOne->conditions);
        $this->assertEquals(63, $dayOne->high);
        $this->assertEquals(45, $dayOne->low);
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(17, $dayOne->high);
        $this->assertEquals(7, $dayOne->low);

        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $dayTwo = $forecast->getForecastDay(1);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Base', $dayTwo);
        $this->assertEquals('Mostly Sunny', $dayTwo->conditions);
        $this->assertEquals(58, $dayTwo->high);
        $this->assertEquals(49, $dayTwo->low);
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(14, $dayTwo->high);
        $this->assertEquals(9, $dayTwo->low);

        // Test unsupported
        $this->assertEquals(false, $dayOne->rain_total);
        $this->assertEquals(false, $dayOne->snow_total);

        // Test unknown throws exception
        $this->setExpectedException('Horde_Service_Weather_Exception_InvalidProperty');
        $this->assertEquals(false, $dayOne->foobar);
    }

    private function _getStub($fixture, $language = 'en')
    {
        $body = fopen(__DIR__ . '/fixtures/' . $fixture, 'r');
        $response = new Horde_Http_Response_Mock('', $body);
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);

        return new Horde_Service_Weather_Google(
            array(
                'language' => $language,
                'http_client' => new Horde_Http_Client(array('request' => $request))
            )
        );
    }

}