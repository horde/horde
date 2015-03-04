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
    public function testDeviceDetection()
    {
        // iOS
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $fixture = array(
            'deviceType' => 'iPod',
            'userAgent' => 'Apple-iPod5C1/1102.55400001',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 7.0.4')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(7, $device->getMajorVersion());
        $this->assertEquals(0, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_IPOD, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_NOTES, $device->multiplex);

        $fixture = array(
            'deviceType' => 'iPhone',
            'userAgent' => 'iOS/6.1.3 (10B329)'
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(6, $device->getMajorVersion());
        $this->assertEquals(1, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_IPHONE, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_NOTES, $device->multiplex);

        // Old Android.
        $fixture = array(
          'userAgent' => 'Android/0.3',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(0, $device->getMajorVersion());
        $this->assertEquals(3, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                    Horde_ActiveSync_Device::MULTIPLEX_CALENDAR |
                    Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                    Horde_ActiveSync_Device::MULTIPLEX_TASKS, $device->multiplex);

        // Touchdown client on Android.
        $fixture = array(
          'userAgent' => 'TouchDown(MSRPC)/7.1.0005',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(7, $device->getMajorVersion());
        $this->assertEquals(1, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_TOUCHDOWN, strtolower($device->clientType));
        $this->assertEquals(0, $device->multiplex);

        // Not-so-old-but-still-old Android.
        $fixture = array(
          'userAgent' => 'MOTOROLA-Droid(4D6F7869SAM)/2.1707',
          'deviceType' => 'Android');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(2, $device->getMajorVersion());
        $this->assertEquals(1707, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(15, $device->multiplex);


        // KK Android (taken from SDK).
        $fixture = array(
            'userAgent' => 'Android/4.4.2-EAS-1.3',
            'deviceType' => 'Android',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android 4.4.2')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(4, $device->getMajorVersion());
        $this->assertEquals(4, $device->getMinorVersion());
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(13, $device->multiplex);

        // Devices like this (from a Note 3) we simply can't sniff multiplex for
        // since there is no version string. Stuff like this would go in the
        // hook.
        $fixture = array(
            'deviceType' => 'SAMSUNGSMN900V',
            'userAgent' => 'SAMSUNG-SM-N900V/101.403',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        // These are useless values, but still tests the reliability of the code
        $this->assertEquals(101, $device->getMajorVersion());
        $this->assertEquals(403, $device->getMinorVersion());
        $this->assertEquals('samsungsmn900v', strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(15, $device->multiplex);

        // Nine (From Note 3 running 4.4.2).
        $fixture = array(
            'deviceType' => 'Android',
            'userAgent' => 'hltevzw/KOT49H',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android 4.4.2.N900VVRUCNC4')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $device->id = '6E696E656331393035333833303331';

        $this->assertEquals(4, $device->getMajorVersion());
        $this->assertEquals('android', strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_NINE, strtolower($device->clientType));
        $this->assertEquals(0, $device->multiplex);

        // HTCOneMini2
        $fixture = array(
            'userAgent' => 'HTC', // Don't think this matters here.
            'deviceType' => 'HTCOnemini2',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android 4.4.2', Horde_ActiveSync_Device::MODEL => 'HTCOnemini2')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $this->assertEquals(4, $device->getMajorVersion());
        $this->assertEquals(4, $device->getMinorVersion());
        $this->assertEquals('htconemini2', strtolower($device->deviceType));
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, strtolower($device->clientType));
        $this->assertEquals(15, $device->multiplex);
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
        $this->assertEquals('2003-09-24', $bday->setTimezone('America/New_York')->format('Y-m-d'));

        // iOS (Sends as 00:00:00 localtime converted to UTC).
        $fixture = array(
            'deviceType' => 'iPhone',
            'userAgent' => 'Apple-iPhone4C1/1002.329',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 6.1.3 10B329'));
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('1970-03-20');
        $bday = $device->normalizePoomContactsDates($date, true);
        $this->assertEquals('1970-03-20 00:00:00', (string)$bday);

        $date = new Horde_Date('1970-03-20T05:00:00.000Z');
        $bday = $device->normalizePoomContactsDates($date);
        $this->assertEquals('1970-03-20 00:00:00', (string)$bday->setTimezone('America/New_York'));

        // Try a positive UTC offset timezone
        date_default_timezone_set('Europe/Berlin');
        $fixture = array(
            'deviceType' => 'iPhone',
            'userAgent' => 'Apple-iPhone4C1/1104.201',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 7.1.1 11D201'));
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('1966-07-22T23:00:00.000Z');
        $bday = $device->normalizePoomContactsDates($date);
        $bday->setTimezone(date_default_timezone_get());
        $this->assertEquals('1966-07-23', $bday->format('Y-m-d'));

        // Android
        date_default_timezone_set('Pacific/Honolulu');
        $fixture = array('deviceType' => 'android', 'userAgent' => 'Android/4.3.1-EAS-1.3');
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('2003-09-24 08:00:00', 'UTC');
        $bday = $device->normalizePoomContactsDates($date);
        $this->assertEquals('2003-09-24', $bday->setTimezone('Pacific/Honolulu')->format('Y-m-d'));

        // Note 3
        date_default_timezone_set('America/Chicago');
        $fixture = array(
            'deviceType' => 'SAMSUNGSMN900V',
            'userAgent' => 'SAMSUNG-SM-N900V/101.403',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $date = new Horde_Date('1970-03-20');
        $bday = $device->normalizePoomContactsDates($date, true);
        $this->assertEquals('1970-03-20 00:00:00', (string)$bday);

        $fixture = array(
            'deviceType' => 'Android',
            'userAgent' => 'hltevzw/KOT49H',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android 4.4.2.N900VVRUCNC4')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $device->id = '6E696E656331393035333833303331';
        $date = new Horde_Date('1970-03-20');
        $bday = $device->normalizePoomContactsDates($date, true);
        $this->assertEquals('1970-03-20 00:00:00', (string)$bday);

        date_default_timezone_set($tz);
    }

    public function testOverrideClientType()
    {
        $fixture = array(
            'deviceType' => 'SAMSUNGSMN900V',
            'userAgent' => 'SAMSUNG-SM-N900V/101.403',
            'properties' => array(Horde_ActiveSync_Device::OS => 'Android')
        );
        $device = new Horde_ActiveSync_Device($this->getMockSkipConstructor('Horde_ActiveSync_State_Base'), $fixture);
        $this->assertEquals(Horde_ActiveSync_Device::TYPE_ANDROID, $device->clientType);
        $device->clientType = 'Samsung';
        $this->assertEquals('Samsung', $device->clientType);
    }

}