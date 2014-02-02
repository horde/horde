<?php
/*
 * Unit tests for Horde_ActiveSync_Device
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_DeviceTest extends Horde_Test_Case
{
    public function testDeviceGetMajorVersion()
    {
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $fixture = array(
            'deviceType' => 'iPod',
            'userAgent' => 'Apple-iPod5C1/1102.55400001',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 7.0.4')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(7, $device->getMajorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_IPOD, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_NOTES, $device->multiplex);

        $fixture = array(
            'deviceType' => 'iPhone',
            'userAgent' => 'iOS/6.1.3 (10B329)'
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(6, $device->getMajorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_IPHONE, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_NOTES, $device->multiplex);

        $fixture = array(
          'userAgent' => 'Android/0.3',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(0, $device->getMajorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                    Horde_ActiveSync_Device::MULTIPLEX_CALENDAR |
                    Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                    Horde_ActiveSync_Device::MULTIPLEX_TASKS, $device->multiplex);

        $fixture = array(
          'userAgent' => 'TouchDown(MSRPC)/7.1.0005',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(7, $device->getMajorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_TOUCHDOWN, strtolower($device->clientType));
        $this->assertEquals(0, $device->multiplex);

        $fixture = array(
          'userAgent' => 'MOTOROLA-Droid(4D6F7869SAM)/2.1707',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(0, $device->getMajorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_UNKNOWN, strtolower($device->clientType));
        $this->assertEquals(0, $device->multiplex);

        // Devices like this (from a Note 3) we simply can't sniff multiplex for since there
        // is no version string. Stuff like this would go in the hook.
        $fixture = array(
            'deviceType' => 'SAMSUNGSMN900V',
            'userAgent' => 'SAMSUNG-SM-N900V/101.403',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(0, $device->getMajorVersion());
        $this->assertEquals('samsungsmn900v', strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_UNKNOWN, strtolower($device->clientType));
        $this->assertEquals(0, $device->multiplex);
    }

    public function testPoomContactsDate()
    {
        $tz = date_default_timezone_get();

        date_default_timezone_set('America/New_York');
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');

        // WindowsPhone.
        $fixture = array('deviceType' => 'windowsphone');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('2003-09-24', 'UTC');
        $bday = $device->normalizePoomContactsDates($date);
        $this->assertEquals('2003-09-24 00:00:00', (string)$bday);
        $this->assertEquals('America/New_York', $bday->timezone);

        // Android
        date_default_timezone_set('Pacific/Honolulu');
        $fixture = array('deviceType' => 'android', 'userAgent' => 'Android/4.3.1-EAS-1.3');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('2003-09-24 08:00:00', 'UTC');

        $bday = $device->normalizePoomContactsDates($date);
        $this->assertEquals('2003-09-24 00:00:00', (string)$bday);
        $this->assertEquals('Pacific/Honolulu', $bday->timezone);

        date_default_timezone_set($tz);
    }
}