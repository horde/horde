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

class Horde_Service_Weather_WwoTest extends PHPUnit_Framework_TestCase
{

    private $_unsupported = array(
        'pressure_trend', 'logo_url', 'dewpoint', 'wind_gust');

    public function testCurrentConditions()
    {
        $weather = $this->_getStub('boston_wwo.json');

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('boston,ma');

        // Condition
        $this->assertEquals(Horde_Service_Weather_Translation::t("Partly Cloudy"), $conditions->condition);

        // Humidity
        $this->assertEquals('88', $conditions->humidity);

        // Temp (F), Wind Speed (MPH), Visibility (Miles), Pressure (inches)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(54, $conditions->temp);
        $this->assertEquals(15, $conditions->wind_speed);
        $this->assertEquals(10, $conditions->visibility);
        $this->assertEquals(30.12, $conditions->pressure);

        // Temp (C), Wind Speed (KPH), Visibility (K), Pressure (mb)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(12, $conditions->temp);
        $this->assertEquals(24, $conditions->wind_speed);
        $this->assertEquals(16, $conditions->visibility);
        $this->assertEquals(1020, $conditions->pressure);

        // Wind
        $this->assertEquals('SSW', $conditions->wind_direction);
        $this->assertEquals(210, $conditions->wind_degrees);

        //$this->assertEquals('2011-11-27 02:08:00', (string)$conditions->time);

        // Test unsupported properties
        foreach ($this->_unsupported as $property) {
            $this->assertNull($conditions->$property);
        }
    }

    public function testGetStation()
    {
        $weather = $this->_getStub('boston_wwo.json');
        $weather->getCurrentConditions('boston,ma');
        $station = $weather->getStation();

        //$this->assertEquals('2011-11-27 06:49:57', (string)$station->sunrise);
        //$this->assertEquals('2011-11-27 16:14:09', (string)$station->sunset);
        $this->assertEquals('Boston, Massachusetts', $station->name);
    }

    public function testForecast()
    {
        $weather = $this->_getStub('boston_wwo.json');
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;

        $forecast = $weather->getForecast('boston,ma');
        //$this->assertEquals('2011-11-27 02:08:00', (string)$forecast->getForecastTime());

        $dayOne = $forecast->getForecastDay(0);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Base', $dayOne);
        $this->assertEquals(Horde_Service_Weather_Translation::t("Sunny"), $dayOne->conditions);
        $this->assertEquals(52, $dayOne->high);
        $this->assertEquals(42, $dayOne->low);
        $this->assertEquals(10, $dayOne->wind_speed);

        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(11, $dayOne->high);
        $this->assertEquals(5, $dayOne->low);
        $this->assertEquals('ESE', $dayOne->wind_direction);
        $this->assertEquals('106', $dayOne->wind_degrees);
        $this->assertEquals(16, $dayOne->wind_speed);

        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $dayTwo = $forecast->getForecastDay(1);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Base', $dayTwo);
        $this->assertEquals(Horde_Service_Weather_Translation::t("Sunny"), $dayTwo->conditions);
        $this->assertEquals(57, $dayTwo->high);
        $this->assertEquals(50, $dayTwo->low);
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(14, $dayTwo->high);
        $this->assertEquals(10, $dayTwo->low);

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

        return new Horde_Service_Weather_Wwo(
            array(
                'apikey' => 'xxx',
                'http_client' => new Horde_Http_Client(array('request' => $request))
            )
        );
    }

}