<?php
/**
 * Unit tests Horde_ActiveSync_Message_Appointment objects.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_InviteTest extends Horde_Test_Case
{

    protected $_oldtz;

    public function setUp()
    {
        $this->_oldtz = date_default_timezone_get();
        date_default_timezone_set('America/New_York');
    }

    public function tearDown()
    {
        date_default_timezone_set($this->_oldtz);
    }

    /**
     * Test creating a Horde_ActiveSync_Message_MeetingRequest from a MIME Email
     */
    public function testInvite()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/invitation_one.eml');
        $mime = Horde_Mime_Part::parseMessage($fixture);
        $msg = new Horde_ActiveSync_Message_MeetingRequest();
        foreach ($mime->contentTypeMap() as $id => $type) {
            if ($type == 'text/calendar') {
                $vcal = new Horde_Icalendar();
                $vcal->parseVcalendar($mime->getPart($id)->getContents());
                $msg->fromvEvent($vcal);
                break;
            }
        }

        $stream = fopen('php://memory', 'wb+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $msg->encodeStream($encoder);
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);

        $stream = fopen(__DIR__ . '/fixtures/meeting_request_one.wbxml', 'r+');
        $expected = '';
        // Using file_get_contents or even fread mangles the binary data for some
        // reason.
        while ($line = fgets($stream)) {
            $expected .= $line;
        }
        fclose($stream);
        $this->assertEquals($expected, $results);
    }

    /**
     * Test parsing GOID value.
     */
    public function testParseGlobalObjectId()
    {
        // Outlook UID
        $fixture = 'BAAAAIIA4AB0xbcQGoLgCAfUCRDgQMnBJoXEAQAAAAAAAAAAEAAAAAvw7UtuTulOnjnjhns3jvM=';
        $uid = Horde_ActiveSync_Utils::getUidFromGoid($fixture);
        $this->assertEquals(
          '040000008200E00074C5B7101A82E00800000000E040C9C12685C4010000000000000000100000000BF0ED4B6E4EE94E9E39E3867B378EF3',
          $uid);

        // vCal
        $fixture = 'BAAAAIIA4AB0xbcQGoLgCAAAAAAAAAAAAAAAAAAAAAAAAAAAMwAAAHZDYWwtVWlkAQAAAHs4MTQxMkQzQy0yQTI0LTRFOUQtQjIwRS0xMUY3QkJFOTI3OTl9AA==';
        $uid = Horde_ActiveSync_Utils::getUidFromGoid($fixture);
        $this->assertEquals('{81412D3C-2A24-4E9D-B20E-11F7BBE92799}', $uid);
    }

    /**
     * Test creation of a MAPI GOID value form a UID
     *
     */
    public function testCreateGoid()
    {
        $uid = '{81412D3C-2A24-4E9D-B20E-11F7BBE92799}';
        $expected = 'BAAAAIIA4AB0xbcQGoLgCAAAAAAAAAAAAAAAAAAAAAAAAAAAJgAAAHZDYWwtVWlkAQAAAHs4MTQxMkQzQy0yQTI0LTRFOUQtQjIwRS0xMUY3QkJFOTI3OTl9AA==';

        $results = Horde_ActiveSync_Utils::createGoid($uid);
        $this->assertEquals($expected, $results);
    }

}