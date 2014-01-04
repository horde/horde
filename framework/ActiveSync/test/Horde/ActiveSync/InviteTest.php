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
        $this->markTestIncomplete('Has issues on 32bit systems');
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

}