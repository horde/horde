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
        $tz = new Horde_Timezone_Mock('europe_jersey');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_jersey.ics',
            $tz->getZone('Europe/Jersey')->toVtimezone()->exportVcalendar()
        );
        $tz = new Horde_Timezone_Mock('europe_berlin');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_berlin.ics',
            $tz->getZone('Europe/Berlin')->toVtimezone()->exportVcalendar()
        );
    }

    public function testAliases()
    {
        $tz = new Horde_Timezone_Mock('europe_berlin');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_berlin.ics',
            $tz->getZone('Europe/Berlin')->toVtimezone()->exportVcalendar()
        );
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_berlin.ics',
            $tz->getZone('W. Europe Standard Time')->toVtimezone()->exportVcalendar()
        );
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_berlin.ics',
            $tz->getZone('W. Europe')->toVtimezone()->exportVcalendar()
        );
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/europe_berlin.ics',
            $tz->getZone('CET')->toVtimezone()->exportVcalendar()
        );
    }

    public function testLosAngeles()
    {
        $tz = new Horde_Timezone_Mock('northamerica');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/losangeles.ics',
            $tz->getZone('America/Los_Angeles')->toVtimezone()->exportVcalendar()
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

    public function testBug14221()
    {
        $tz = new Horde_Timezone_Mock('budapest');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/budapest.ics',
            $tz->getZone('Europe/Budapest')->toVtimezone()->exportVcalendar()
        );
    }

    public function testBug14162()
    {
        $tz = new Horde_Timezone_Mock('uruguay');
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/uruguay.ics',
            $tz->getZone('America/Montevideo')->toVtimezone()->exportVcalendar()
        );
    }
}
