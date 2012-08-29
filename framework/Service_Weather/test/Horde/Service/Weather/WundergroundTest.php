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

class Horde_Service_Weather_WundergroundTest extends PHPUnit_Framework_TestCase
{
    public function testCurrentConditions()
    {
        $weather = $this->_getStub('boston_wunderground.json');

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('boston,ma');

        // Condition
        $this->assertEquals(Horde_Service_Weather_Translation::t("Mostly Cloudy"), $conditions->condition);

        // Humidity
        $this->assertEquals('90%', $conditions->humidity);

        // Temp (F), Wind Speed (MPH), Visibility (Miles), Pressure (inches)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(54, $conditions->temp);
        $this->assertEquals(8, $conditions->wind_speed);
        $this->assertEquals(13, $conditions->wind_gust);
        $this->assertEquals(10, $conditions->visibility);
        $this->assertEquals(30.10, $conditions->pressure);
        $this->assertEquals(51, $conditions->dewpoint);

        // Temp (C), Wind Speed (KPH), Visibility (K), Pressure (mb)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(12, $conditions->temp);
        $this->assertEquals(13, $conditions->wind_speed);
        $this->assertEquals(21, $conditions->wind_gust);
        $this->assertEquals(16, $conditions->visibility);
        $this->assertEquals(1019.2, $conditions->pressure);
        $this->assertEquals(11, $conditions->dewpoint);

        $this->assertEquals('WSW', $conditions->wind_direction);
        $this->assertEquals(237, $conditions->wind_degrees);
        $this->assertEquals(Horde_Service_Weather_Translation::t("falling"), $conditions->pressure_trend);
        $this->assertEquals('2011-11-27 23:10:25', (string)$conditions->time);
    }

    public function testGetStation()
    {
        $weather = $this->_getStub('boston_wunderground.json');
        $weather->getCurrentConditions('boston,ma');
        $station = $weather->getStation();

        $this->assertEquals('2011-11-27 06:48:00', (string)$station->sunrise);
        $this->assertEquals('2011-11-27 16:14:00', (string)$station->sunset);
        $this->assertEquals('Boston, MA', $station->name);
    }

    public function testForecast()
    {
        setlocale(LC_MESSAGES, 'C');
        $weather = $this->_getStub('boston_wunderground.json');
        $forecast = $weather->getForecast('boston,ma');
        //$this->assertEquals('2011-11-27 22:15:00', (string)$forecast->getForecastTime());

        $dayOne = $forecast->getForecastDay(0);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Base', $dayOne);
        $this->assertEquals(Horde_Service_Weather_Translation::t("Partly Cloudy"), $dayOne->conditions);
        $this->assertEquals('South', $dayOne->wind_direction);
        $this->assertEquals('187', $dayOne->wind_degrees);
        $this->assertEquals(80, $dayOne->humidity);
        $this->assertEquals(0, $dayOne->precipitation_percent);

        $this->assertEquals(58, $dayOne->high);
        $this->assertEquals(50, $dayOne->low);
        $this->assertEquals(8, $dayOne->wind_speed);
        $this->assertEquals(11, $dayOne->wind_gust);
        $this->assertEquals(0.63, $dayOne->rain_total);

        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(14, $dayOne->high);
        $this->assertEquals(10, $dayOne->low);
        $this->assertEquals(13, $dayOne->wind_speed);
        $this->assertEquals(18, $dayOne->wind_gust);

        $this->assertEquals(0, $dayOne->snow_total);
        $this->assertEquals(16, $dayOne->rain_total);
    }

    private function _getStub($fixture, $language = 'en')
    {
        $body = fopen(__DIR__ . '/fixtures/' . $fixture, 'r');
        $response = new Horde_Http_Response_Mock('', $body);
        $response->code = 200;
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);

        return new Horde_Service_Weather_WeatherUnderground(
            array(
                'apikey' => 'xxx',
                'http_client' => new Horde_Http_Client(array('request' => $request))
            )
        );
    }

}