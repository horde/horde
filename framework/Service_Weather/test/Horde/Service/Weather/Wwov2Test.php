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
class Horde_Service_Weather_Wwov2Test extends Horde_Test_Case
{
    protected $_mockUrls = array(
        'https://api.worldweatheronline.com/free/v2/weather.ashx?q=39.660%2C-75.093&num_of_days=5&includeLocation=yes&extra=localObsTime&tp=24&showlocaltime=yes&showmap=yes&format=json&key=xxx' => 'wwov2.json');

    protected $_unsupported = array('pressure_trend', 'logo_url', 'dewpoint');

    public function testCurrentConditions()
    {
        $weather = $this->_getStub();

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('clayton,nj');

        // Condition
        $this->assertEquals(Horde_Service_Weather_Translation::t("Clear"), $conditions->condition);

        // Humidity
        $this->assertEquals('57%', $conditions->humidity);

        // Temp (F), Wind Speed (MPH), Visibility (Miles), Pressure (inches)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(73, $conditions->temp);
        $this->assertEquals(4, $conditions->wind_speed);
        // Not 100% sure about this. Docs say it is returned in KM as well
        // as Miles, but the Miles field seems to be missing.
        $this->assertEquals(10, $conditions->visibility);
        $this->assertEquals(30.18, $conditions->pressure);

        // // Temp (C), Wind Speed (KPH), Visibility (K), Pressure (mb)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(23, $conditions->temp);
        $this->assertEquals(6, $conditions->wind_speed);
        $this->assertEquals(16, $conditions->visibility);
        $this->assertEquals(1022, $conditions->pressure);

        // Wind
        $this->assertEquals('SE', $conditions->wind_direction);
        $this->assertEquals(140, $conditions->wind_degrees);
        $this->assertEquals('2015-08-28 23:53:00', (string)$conditions->time);

        // Test unsupported properties
        foreach ($this->_unsupported as $property) {
            $this->assertNull($conditions->$property);
        }
    }

    public function testGetStation()
    {
        $weather = $this->_getStub();
        $weather->getCurrentConditions('boston,ma');
        $station = $weather->getStation();

        $this->assertEquals('Boston, Massachusetts', $station->name);
        $this->assertEquals('-04:00', $station->getOffset());
    }

    public function testForecast()
    {
        $weather = $this->_getStub();
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;

        $forecast = $weather->getForecast('clayton,nj');
        $this->assertEquals('2015-08-28 23:53:00', (string)$forecast->getForecastTime());

        $dayOne = $forecast->getForecastDay(0);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Wwov2', $dayOne);
        $this->assertEquals(Horde_Service_Weather_Translation::t("Sunny"), $dayOne->conditions);
        $this->assertEquals(89, $dayOne->high);
        $this->assertEquals(65, $dayOne->low);
        $this->assertEquals(7, $dayOne->wind_speed);

        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $this->assertEquals(32, $dayOne->high);
        $this->assertEquals(18, $dayOne->low);
        $this->assertEquals('NW', $dayOne->wind_direction);
        $this->assertEquals('304', $dayOne->wind_degrees);
        $this->assertEquals(11, $dayOne->wind_speed);

        // Test unknown throws exception
        $this->setExpectedException('Horde_Service_Weather_Exception_InvalidProperty');
        $this->assertEquals(false, $dayOne->foobar);
    }

    protected function _getHttpClientStub()
    {
        $request = $this->getMockSkipConstructor('Horde_Http_Client');
        $request->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'mockHttpCallback')));

        return $request;
    }

    protected function _getStub()
    {
        return new Horde_Service_Weather_Wwo(
            array(
                'apikey' => 'xxx',
                'http_client' => $this->_getHttpClientStub(),
                'apiVersion' => 2
            )
        );
    }

    public function mockHttpCallback($url)
    {
        switch ((string)$url) {
        case 'https://api.worldweatheronline.com/free/v2/weather.ashx?q=clayton%2Cnj&num_of_days=5&includeLocation=yes&extra=localObsTime&tp=24&showlocaltime=yes&showmap=yes&format=json&key=xxx':
            $stream = fopen(__DIR__ . '/fixtures/wwov2.json', 'r');
            break;
        case 'https://api.worldweatheronline.com/free/v2/search.ashx?timezone=yes&q=39.660%2C-75.093&num_of_results=10&format=json&key=xxx':
            $stream = fopen(__DIR__ . '/fixtures/boston_location_wwo.json', 'r');
            break;
        case 'https://api.worldweatheronline.com/free/v2/weather.ashx?q=boston%2Cma&num_of_days=5&includeLocation=yes&extra=localObsTime&tp=24&showlocaltime=yes&showmap=yes&format=json&key=xxx':
            $stream = fopen(__DIR__ . '/fixtures/wwov2.json', 'r');
            break;
        default:
            throw new Exception(sprintf('Invalid Url: %s', (string)$url));
        }
        $response = new Horde_Http_Response_Mock($url, $stream);
        $response->code = 200;

        return $response;
    }

}
