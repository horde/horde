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

    public function testItipReply()
    {
        $eml = file_get_contents(__DIR__ . '/fixtures/Itip_Reply.eml');
        $mime = Horde_Mime_Part::parseMessage($eml);
        $winmail = $mime->getPart(2)->getContents();
        $tnef = Horde_Compress::factory('Tnef');
        $tnef_data = $tnef->decompress($winmail);
    }

    public function testvNote()
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
        $fixture = file_get_contents(__DIR__ . '/fixtures/tnef_vtodo_fixture.txt');
        $this->assertEquals($fixture, $tnef_data[0]['stream']);
    }

    public function testMeetingInvitation()
    {
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
