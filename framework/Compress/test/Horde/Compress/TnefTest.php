<?php
/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_TnefTest extends Horde_Test_Case
{
    public $testdata;

    protected function setUp()
    {
        if (!class_exists('Horde_Mapi')) {
            $this->markTestSkipped('Horde_Mapi is not available');
        }
    }

    /**
     * @requires extension bcmath
     */
    public function testItipReply()
    {
        $this->markTestSkipped('Currently throws exception, since we do not know how to decode yet.');
        $log = new Horde_Test_Log();
        $eml = file_get_contents(__DIR__ . '/fixtures/Itip_Reply.eml');
        $mime = Horde_Mime_Part::parseMessage($eml);
        $winmail = $mime->getPart(2)->getContents();
        $tnef = Horde_Compress::factory('Tnef', array('logger' => $log->getLogger()));
        $tnef_data = $tnef->decompress($winmail);
    }

    /**
     * @requires extension bcmath
     */
    public function testvTodo()
    {
        $tnef = Horde_Compress::factory('Tnef');
        $data = base64_decode(file_get_contents(__DIR__ . '/fixtures/tnef_vnote'));
        try {
            $tnef_data = $tnef->decompress($data);
        } catch (Horde_Mapi_Exception $e) {
            $this->markTestSkipped('Horde_Mapi is not available');
        } catch (Horde_Compress_Exception $e) {
            var_dump($e);
        }
    }

    /**
     * @requires extension bcmath
     */
    public function testMeetingInvitation()
    {
        $this->markTestIncomplete('Fails with Horde_Compress_Exception: TNEF: Unknown attribute type, "0x810F"');
        $tnef = Horde_Compress::factory('Tnef');
        $data = base64_decode(file_get_contents(__DIR__ . '/fixtures/TnefMeetingRequest.txt'));
        try {
            $tnef_data = $tnef->decompress($data);
        } catch (Horde_Compress_Exception $e) {
            if (($prev = $e->getPrevious()) &&
                ($prev instanceof Horde_Mapi_Exception)) {
                $this->markTestSkipped();
            }
            throw $e;
        }
        $this->assertEquals($tnef_data[0]['type'], 'text');
        $this->assertEquals($tnef_data[0]['subtype'], 'calendar');
        $this->assertEquals($tnef_data[0]['name'], 'Meeting');
    }

    /**
     * @requires extension bcmath
     */
    public function testMeetingTnef()
    {
        $winmail = file_get_contents(__DIR__ . '/fixtures/winmail2.dat');
        $tnef = Horde_Compress::factory('Tnef');
        $tnef_data = $tnef->decompress($winmail);

        // Test the meta data
        $this->assertEquals($tnef_data[0]['type'], 'text');
        $this->assertEquals($tnef_data[0]['subtype'], 'calendar');
        $this->assertEquals($tnef_data[0]['name'], 'Test Meeting');

        // Test the generated iCalendar.
        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($tnef_data[0]['stream'])) {
            throw new Horde_Compress_Exception(_("There was an error importing the iCalendar data."));
        }
        $components = $iCal->getComponents();
        if (count($components) == 0) {
            throw new Horde_Compress_Exception(_("No iCalendar data was found."));
        }
        $iTip = current($components);
        $this->assertEquals($iTip->getAttribute('SUMMARY'), 'Test Meeting');
        $this->assertEquals($iTip->getAttribute('DESCRIPTION'), 'This is a test meeting.');
        $this->assertEquals($iTip->getAttribute('ORGANIZER'), 'mailto:mike@theupstairsroom.com');
        $this->assertEquals($iTip->getAttribute('UID'), 'D38D34D34D34F36D347B4D34EF87396F00000000367B4D3CD34D34D34D34EF4774D3877BF3ADDA774D35D34D34D34D34D34D34D34D34D74D34D34D34D3471CF747DB6F469FE5EE34F386FCE75F79DFC6B675FE9D');
        $this->assertEquals($iTip->getAttribute('ATTENDEE'), 'mrubinsk@horde.org');
        $params = $iTip->getAttribute('ATTENDEE', true);
        if (!$params) {
            throw new Horde_Compress_Exception('Could not find expected parameters.');
        }
        $this->assertEquals($params[0]['ROLE'], 'REQ-PARTICIPANT');
        $this->assertEquals($params[0]['PARTSTAT'], 'NEEDS-ACTION');
        $this->assertEquals($params[0]['RSVP'], 'TRUE');
    }

    public function testAttachments()
    {
        $data = base64_decode(file_get_contents(__DIR__ . '/fixtures/TnefAttachments.txt'));
        $tnef = Horde_Compress::factory('Tnef');
        $tnef_data = $tnef->decompress($data);
        $this->assertEquals('application', $tnef_data[0]['type']);
        $this->assertEquals('rtf', $tnef_data[0]['subtype']);
        $this->assertEquals('image', $tnef_data[1]['type']);
        $this->assertEquals('jpeg', $tnef_data[1]['subtype']);
        $this->assertEquals('hasselhoff_birthday.jpg', $tnef_data[1]['name']);
        $this->assertEquals(80051, $tnef_data[1]['size']);
    }

    public function testMultipleAttachments()
    {
        $data = base64_decode(file_get_contents(__DIR__ . '/fixtures/TnefAttachmentsMultiple.txt'));
        $tnef = Horde_Compress::factory('Tnef');
        $tnef_data = $tnef->decompress($data);
        $this->assertEquals('application', $tnef_data[0]['type']);
        $this->assertEquals('rtf', $tnef_data[0]['subtype']);
        $this->assertEquals('image', $tnef_data[1]['type']);
        $this->assertEquals('jpeg', $tnef_data[1]['subtype']);
        $this->assertEquals('Lighthouse.jpg', $tnef_data[1]['name']);
        $this->assertEquals('image', $tnef_data[2]['type']);
        $this->assertEquals('jpeg', $tnef_data[2]['subtype']);
        $this->assertEquals('Penguins.jpg', $tnef_data[2]['name']);
    }

}
