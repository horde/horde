<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Timezone
 * @subpackage UnitTests
 */

class Horde_Timezone_IcalendarTest extends Horde_Test_Case
{
    public function testEurope()
    {
        $tz = new Horde_Timezone_Mock('europe');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe.ics',
            $tz->getZone('Europe/Jersey')->toVtimezone()->exportVcalendar()
        );
    }

    public function testEtc()
    {
        $tz = new Horde_Timezone_Mock('etcetera');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/etcetera.ics',
            $tz->getZone('Etc/UTC')->toVtimezone()->exportVcalendar()
        );
    }
}

class Horde_Timezone_Mock extends Horde_Timezone
{
    protected $_zone;

    public function __construct($zone)
    {
        parent::__construct();
        $this->_zone = $zone;
    }

    protected function _download()
    {
    }

    protected function _extractAndParse()
    {
        $this->_parse(file_get_contents(__DIR__ . '/fixtures/' . $this->_zone));
    }
}
