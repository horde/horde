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

    public function testInvite()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/invitation_one.eml');
        $mime = Horde_Mime_Part::parseMessage($fixture);
        $msg = new Horde_ActiveSync_Message_MeetingRequest();
        foreach ($mime->contentTypeMap() as $id => $type) {
            if ($type == 'text/calendar') {
                $msg->fromMimePart($mime->getPart($id));
                break;
            }
        }

        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $msg->encodeStream($encoder);
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);
        $expected = file_get_contents(__DIR__ . '/fixtures/meeting_request_one.wbxml');
        $this->assertEquals($expected, $results);
    }
}