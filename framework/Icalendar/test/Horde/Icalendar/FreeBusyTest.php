<?php
/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_FreeBusyTest extends Horde_Test_Case
{
    public function testRead()
    {
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar(file_get_contents(__DIR__ . '/fixtures/vfreebusy1.ics'));

        // Get the vFreeBusy component
        $vfb = $ical->getComponent(0);

        // Dump the type
        $this->assertEquals(
            'vFreebusy',
            $vfb->getType()
        );

        // Dump the vfreebusy component again (the duration should be
        // converted to start/end
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/vfreebusy2.ics',
            $vfb->exportvCalendar()
        );

        // Dump organizer name
        $this->assertEquals(
            'GunnarWrobel',
            $vfb->getName()
        );

        // Dump organizer mail
        $this->assertEquals(
            'wrobel@demo2.pardus.de',
            $vfb->getEmail()
        );

        // Dump busy periods
        $this->assertEquals(
            array(
               1164258000 => 1164261600,
               1164268800 => 1164276000
            ),
            $vfb->getBusyPeriods()
        );

        // Decode the summary information
        $extra = $vfb->getExtraParams();
        $this->assertEquals(
            'testtermin',
            base64_decode($extra[1164258000]['X-SUMMARY'])
        );

        // Dump the free periods in between the two given time stamps
        $this->assertEquals(
            array(
               1164261600 => 1164268800,
            ),
            $vfb->getFreePeriods(1164261500, 1164268900)
        );

        // Dump start of the free/busy information
        $this->assertEquals(
            1164236400,
            $vfb->getStart()
        );

        // Dump end of the free/busy information
        $this->assertEquals(
            1169420400,
            $vfb->getEnd()
        );

        // Free periods don't get added
        $vfb->addBusyPeriod('FREE', 1164261600, 1164268800);
        $this->assertEquals(
            array(
               1164258000 => 1164261600,
               1164268800 => 1164276000
            ),
            $vfb->getBusyPeriods()
        );

        // Add a busy period with start/end (11:00 / 12:00)
        $vfb->addBusyPeriod('BUSY', 1164279600, 1164283200);

        // Add a busy period with start/duration (14:00 / 2h)
        $vfb->addBusyPeriod('BUSY', 1164290400, null, 7200, array('X-SUMMARY' => 'dGVzdA==')
        );

        // Dump busy periods
        $this->assertEquals(
            array(
               1164258000 => 1164261600,
               1164268800 => 1164276000,
               1164279600 => 1164283200,
               1164290400 => 1164297600,
            ),
            $vfb->getBusyPeriods()
        );

        // Dump the extra parameters
        $this->assertEquals(
            array(
               1164258000 => array(
                   'X-UID' => 'MmZlNWU3NDRmMGFjNjZkNjRjZjFkZmFmYTE4NGFiZTQ=',
                   'X-SUMMARY' => 'dGVzdHRlcm1pbg=='
               ),
               1164268800 => array(),
               1164279600 => array(),
               1164290400 => array('X-SUMMARY' => 'dGVzdA=='),
            ),
            $vfb->getExtraParams()
        );

        return $vfb;
    }

    /**
     * @depends testRead
     */
    public function testMerge($vfb)
    {
        // Create new freebusy object for merging
        $mfb = new Horde_Icalendar_Vfreebusy();
        // 1. 3:55 / 10 minutes; summary "test4"
        $mfb->addBusyPeriod('BUSY', 1164254100, null, 600,
                            array('X-SUMMARY' => 'dGVzdDQ='));
        // 2. 4:00 / 1 hours 5 Minutes; summary "test3"
        $mfb->addBusyPeriod('BUSY', 1164254400, null, 3900,
                            array('X-SUMMARY' => 'dGVzdDM='));
        // 3. 5:55 / 10 minutes hours; summary "test5"
        $mfb->addBusyPeriod('BUSY', 1164261300, null, 600,
                            array('X-SUMMARY' => 'dGVzdDU=='));
        // 4. 7:55 / 10 min
        $mfb->addBusyPeriod('BUSY', 1164268500, null, 600);
        // 5. 9:55 / 10 min
        $mfb->addBusyPeriod('BUSY', 1164275700, null, 600);
        // 6. 11:00 / 4 hours; summary "test2"
        $mfb->addBusyPeriod('BUSY', 1164279600, null, 14400,
                            array('X-SUMMARY' => 'dGVzdDI='));
        // 7. 14:00 / 2 min
        $mfb->addBusyPeriod('BUSY', 1164290400, null, 120);
        // 8. 14:30 / 5 min; summary "test3"
        $mfb->addBusyPeriod('BUSY', 1164292200, null, 300,
                            array('X-SUMMARY' => 'dGVzdDM='));
        // 9. 15:55 / 5 min
        $mfb->addBusyPeriod('BUSY', 1164297300, 1164297600);

        // Dump busy periods
        $this->assertEquals(
            array(
               1164254100 => 1164254700,
               1164254400 => 1164258300,
               1164261300 => 1164261900,
               1164268500 => 1164269100,
               1164275700 => 1164276300,
               1164279600 => 1164294000,
               1164290400 => 1164290520,
               1164292200 => 1164292500,
               1164297300 => 1164297600,
            ),
            $mfb->getBusyPeriods()
        );

        $mfb->setAttribute('DTSTART', 1004297300);
        $mfb->setAttribute('DTEND', 1014297300);

        // Merge freebusy components without simplification
        $vfb->merge($mfb, false);

        $this->assertEquals(
            1004297300,
            $vfb->getAttribute('DTSTART')
        );
        $this->assertEquals(
            1169420400,
            $vfb->getAttribute('DTEND')
        );

        // Merged periods (there are some entries having the same start time ->
        // merged to longer of the two)
        $busy = $vfb->getBusyPeriods();
        $extra = $vfb->getExtraParams();

        $this->assertEquals(
            array(
               1164258000 => 1164261600,
               1164268800 => 1164276000,
               1164279600 => 1164294000,
               1164290400 => 1164297600,
               1164254100 => 1164254700,
               1164254400 => 1164258300,
               1164261300 => 1164261900,
               1164268500 => 1164269100,
               1164275700 => 1164276300,
               1164292200 => 1164292500,
               1164297300 => 1164297600,
            ),
            $busy
        );

        // Check merging process (should have selected longer period)
        // and dump extra information alongside
        //   4 hours (instead of 2 hours)
        $this->assertEquals(
            14400,
            $busy[1164279600] - 1164279600
        );
        $this->assertEquals(
            'test2',
            base64_decode($extra[1164279600]['X-SUMMARY'])
        );

        //   2 hours (instead of 2 minutes)
        $this->assertEquals(
            7200,
            $busy[1164290400] - 1164290400
        );
        $this->assertEquals(
            'test',
            base64_decode($extra[1164290400]['X-SUMMARY'])
        );

        // Merge freebusy components again, simplify this time
        $vfb->merge($mfb);

        // Dump merged periods
        $busy =  $vfb->getBusyPeriods();
        $extra = $vfb->getExtraParams();

        // 1. 3:55 / 10 Minutes / test4
        $this->assertEquals(
            '20061123T035500Z',
            $vfb->_exportDateTime(1164254100)
        );
        $this->assertEquals(
            '20061123T040500Z',
            $vfb->_exportDateTime($busy[1164254100])
        );
        $this->assertEquals(
            'test4',
            base64_decode($extra[1164254100]['X-SUMMARY'])
        );

        // 2. 4:05 / 1 hour / test3
        $this->assertEquals(
            '20061123T040500Z',
            $vfb->_exportDateTime(1164254700)
        );
        $this->assertEquals(
            '20061123T050500Z',
            $vfb->_exportDateTime($busy[1164254700])
        );
        $this->assertEquals(
            'test3',
            base64_decode($extra[1164254700]['X-SUMMARY'])
        );

        // 3. 5:05 / 55 Minutes / testtermin
        $this->assertEquals(
            '20061123T050500Z',
            $vfb->_exportDateTime(1164258300)
        );
        $this->assertEquals(
            '20061123T060000Z',
            $vfb->_exportDateTime($busy[1164258300])
        );
        $this->assertEquals(
            'testtermin',
            base64_decode($extra[1164258300]['X-SUMMARY'])
        );

        // 4. 6:00 / 5 Minutes / test5
        $this->assertEquals(
            '20061123T060000Z',
            $vfb->_exportDateTime(1164261600)
        );
        $this->assertEquals(
            '20061123T060500Z',
            $vfb->_exportDateTime($busy[1164261600])
        );
        $this->assertEquals(
            'test5',
            base64_decode($extra[1164261600]['X-SUMMARY'])
        );

        // 5. 7:55 / 2 hours 10 Minutes
        $this->assertEquals(
            '20061123T075500Z',
            $vfb->_exportDateTime(1164268500)
        );
        $this->assertEquals(
            '20061123T100500Z',
            $vfb->_exportDateTime($busy[1164268500])
        );
        $this->assertArrayNotHasKey(
            'X-SUMMARY',
            $extra[1164268500]
        );

        // 6. 11:00 / 4 hours / test2
        $this->assertEquals(
            '20061123T110000Z',
            $vfb->_exportDateTime(1164279600)
        );
        $this->assertEquals(
            '20061123T150000Z',
            $vfb->_exportDateTime($busy[1164279600])
        );
        $this->assertEquals(
            'test2',
            base64_decode($extra[1164279600]['X-SUMMARY'])
        );

        // 7. 15:00 / 1 hour / test
        $this->assertEquals(
            '20061123T150000Z',
            $vfb->_exportDateTime(1164294000)
        );
        $this->assertEquals(
            '20061123T160000Z',
            $vfb->_exportDateTime($busy[1164294000])
        );
        $this->assertEquals(
            'test',
            base64_decode($extra[1164294000]['X-SUMMARY'])
        );
    }
}
