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
class Horde_Service_Weather_MetarTest extends Horde_Test_Case
{
    public function testCurrentConditions()
    {
        $weather = $this->_getWeatherDriver();

        // Note the location here doesn't matter, since we already
        // stubbed the http_client
        $conditions = $weather->getCurrentConditions('KPHL');

        // Condition
        $this->assertEquals('Wind from SW at 15mph Visibility AT 10 miles Sky scattered', $conditions->condition);

        // Humidity (calculated from temp/duepoint)
        $this->assertEquals('50%', $conditions->humidity);

        // Temp (F), Wind Speed (MPH), Visibility (Miles), Pressure (inches)
        $weather->units = Horde_Service_Weather::UNITS_STANDARD;
        $this->assertEquals(97, $conditions->temp);
        $this->assertEquals(15, $conditions->wind_speed);
        $this->assertEquals(10, $conditions->visibility);
        $this->assertEquals(29.87, $conditions->pressure);

        // Temp (C), Wind Speed (KPH), Visibility (K), Pressure (mb)
        $weather->units = Horde_Service_Weather::UNITS_METRIC;
        $conditions = $weather->getCurrentConditions('KPHL');
        $this->assertEquals(36, $conditions->temp);
        $this->assertEquals(24, $conditions->wind_speed);
        $this->assertEquals(16, $conditions->visibility);
        $this->assertEquals(1011.51, $conditions->pressure);

        // Wind
        $this->assertEquals('SW', $conditions->wind_direction);
        $this->assertEquals(220, $conditions->wind_degrees);
        $this->assertEquals('2016-08-12 15:54:00', (string)$conditions->time);

        // METAR specific stuff.
        $this->assertEquals(Horde_Service_Weather_Translation::t('scattered'), $conditions->clouds[0]['amount']);
        $this->assertEquals(4300, $conditions->clouds[0]['height']);
        $this->assertEquals(Horde_Service_Weather_Translation::t('broken'), $conditions->clouds[1]['amount']);
        $this->assertEquals(25000, $conditions->clouds[1]['height']);

        $this->assertEquals(
            Horde_Service_Weather_Translation::t('Automatic weatherstation w/ precipitation discriminator'),
            $conditions->remark['autostation']
        );
        $this->assertEquals(
            1115,
            $conditions->remark['seapressure']
        );
        $this->assertEquals(
            35.6,
            $conditions->remark['1htemp']
        );
        $this->assertEquals(
            23.9,
            $conditions->remark['1hdew']
        );

    }

    public function testForecast()
    {
        $weather = $this->_getWeatherDriver();

        $forecast = $weather->getForecast('KSAW');
        $this->assertEquals('2016-08-12 23:59:00', (string)$forecast->getForecastTime());

        $dayOne = $forecast->getForecastDay(0);
        $this->assertInstanceOf('Horde_Service_Weather_Period_Taf', $dayOne);
        $this->assertEquals('Wind from NNE at 8mph Visibility BEYOND 6 miles Sky overcast', $dayOne->conditions);
        $this->assertEquals(8, $dayOne->wind);
        $this->assertEquals('NNE', $dayOne->wind_direction);
        $this->assertEquals(20, $dayOne->wind_degrees);

        $dayTwo = $forecast->getForecastDay(1);
        $this->assertEquals('Visibility AT 3 miles mist Sky overcast', $dayTwo->conditions);

        // Test unknown throws exception
        $this->setExpectedException('Horde_Service_Weather_Exception_InvalidProperty');
        $this->assertEquals(false, $dayOne->foobar);
    }

    protected function _getWeatherDriver()
    {
        return new Horde_Service_Weather_Metar(array(
            'metar_path' => dirname(__FILE__) . '/fixtures/metar',
            'taf_path' => dirname(__FILE__) . '/fixtures/taf'
        ));
    }
}
