<?php
/**
 * Unit tests Horde_ActiveSync_Message_Appointment objects.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_AppointmentTest extends Horde_Test_Case
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
     * Checks that setting/getting non-existant properties throws an exception.
     */
    public function testEncoding()
    {
        $appt = new Horde_ActiveSync_Message_Appointment();
        $appt->setSubject('Event Title');
        $appt->setBody('Event Description');
        $appt->setLocation('Philadelphia, PA');
        $start = new Horde_Date('2011-12-01T15:00:00');
        $appt->setDatetime(array(
            'start' => $start,
            'end' => new Horde_Date('2011-12-01T16:00:00'),
            'allday' => false)
        );
        $appt->setTimezone($start);
        $appt->setSensitivity(Horde_ActiveSync_Message_Appointment::SENSITIVITY_PERSONAL);
        $appt->setBusyStatus(Horde_ActiveSync_Message_Appointment::BUSYSTATUS_BUSY);
        $appt->setDTStamp($start->timestamp());

        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $appt->encodeStream($encoder);

        $fixture = file_get_contents(dirname(__FILE__) . '/fixtures/appointment.wbxml');
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals($fixture, $results);
    }

    public function testDecoding()
    {
        $stream = fopen(dirname(__FILE__) . '/fixtures/appointment.wbxml', 'r+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream);
        $decoder->setCodePage(4);
        // Version
        $entity = $decoder->getElement();
        $appt = new Horde_ActiveSync_Message_Appointment();
        $appt->decodeStream($decoder);
        fclose($stream);

        $this->assertEquals('Event Title', $appt->subject);
        $this->assertEquals('Event Description', $appt->body);
        $this->assertEquals('Philadelphia, PA', $appt->location);
        $this->assertEquals(Horde_ActiveSync_Message_Appointment::SENSITIVITY_PERSONAL, (integer)$appt->sensitivity);
        $this->assertEquals(Horde_ActiveSync_Message_Appointment::BUSYSTATUS_BUSY, (integer)$appt->busystatus);

        $start = clone($appt->starttime);
        // Ensure it's UTC
        $this->assertEquals('UTC', $start->timezone);

        //...and correct.
        $start->setTimezone('America/New_York');
        $this->assertEquals('2011-12-01 15:00:00', (string)$start);
    }

}
