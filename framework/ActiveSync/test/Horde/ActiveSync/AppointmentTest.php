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
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
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
        $encoder->setLogger($logger);

        $encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $appt->encodeStream($encoder);
        $encoder->endTag();
        $fixture = file_get_contents(__DIR__ . '/fixtures/appointment.wbxml');
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals($fixture, $results);
    }

    public function testDecoding()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        $stream = fopen(__DIR__ . '/fixtures/appointment.wbxml', 'r+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream);
        $decoder->setLogger($logger);

        $element = $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
        $appt->decodeStream($decoder);
        fclose($stream);
        $decoder->getElementEndTag();

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

    public function testEncodingRecurrence()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Every other week recurrence, on thursday, no end.
        $r = new Horde_Date_Recurrence('2011-12-01T15:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_THURSDAY);

        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
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
        $appt->setRecurrence($r);

        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $encoder->setLogger($logger);

        $encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $appt->encodeStream($encoder);
        $encoder->endTag();

        $fixture = file_get_contents(__DIR__ . '/fixtures/recurrence.wbxml');
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);
        $this->assertEquals($fixture, $results);
    }

    public function testDecodingRecurrence()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Test Decoding
        $stream = fopen(__DIR__ . '/fixtures/recurrence.wbxml', 'r+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream);

        $element = $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
        $appt->decodeStream($decoder);
        fclose($stream);
        $decoder->getElementEndTag();

        // Same properties that are testing in testDeoding, but test again
        // here to be sure recurrence doesn't mess up the deocder.
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

        // Recurrence properties
        $rrule = $appt->getRecurrence();
        $this->assertEquals('2011-12-01 15:00:00', (string)$rrule->getRecurStart()->setTimezone('America/New_York'));
        $this->assertEquals('', (string)$rrule->getRecurEnd());
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $rrule->getRecurType());
        $this->assertEquals(2, $rrule->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $days = $rrule->getRecurOnDays());
    }

    public function testEncodingSimpleExceptions()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();
        //$logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen('/tmp/test.log', 'a')));

        // Every other week recurrence, on thursday, no end.
        $r = new Horde_Date_Recurrence('2011-12-01T15:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_THURSDAY);
        $r->addException(2011, 12, 29);

        $e = new Horde_ActiveSync_Message_Exception();
        $d = new Horde_Date('2011-12-29T15:00:00');
        $e->setExceptionStartTime($d);
        $e->deleted = true;

        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
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
        $appt->setRecurrence($r);
        $appt->addException($e);

        $stream = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream);
        $encoder->setLogger($logger);
        $encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $appt->encodeStream($encoder);
        $encoder->endTag();

        $fixture = file_get_contents(__DIR__ . '/fixtures/simpleexception.wbxml');
        rewind($stream);
        $results = stream_get_contents($stream);
        fclose($stream);
        $this->assertEquals($fixture, $results);
    }

    public function testDecodingSimpleExceptions()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Test Decoding
        $stream = fopen(__DIR__ . '/fixtures/simpleexception.wbxml', 'r+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream);

        $element = $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
        $appt->decodeStream($decoder);
        fclose($stream);
        $decoder->getElementEndTag();

        // Same properties that are testing in testDeoding, but test again
        // here to be sure recurrence doesn't mess up the deocder.
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

        // Recurrence properties
        $rrule = $appt->getRecurrence();
        $this->assertEquals('2011-12-01 15:00:00', (string)$rrule->getRecurStart()->setTimezone('America/New_York'));
        $this->assertEquals('', (string)$rrule->getRecurEnd());
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $rrule->getRecurType());
        $this->assertEquals(2, $rrule->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $days = $rrule->getRecurOnDays());


        // Ensure the exception came over (should have one, deleted exception
        // on 2011-12-29)
        $exceptions = $appt->getExceptions();
        $e = array_pop($exceptions);
        $this->assertEquals(true, (boolean)$e->deleted);
        $dt = $e->getExceptionStartTime();
        $rrule->addException($dt->format('Y'), $dt->format('m'), $dt->format('d'));

        // This would normally be 2011-12-29, but that's an exception.
        $date = $rrule->nextActiveRecurrence(new Horde_Date('2011-12-16'));
        $this->assertEquals('2012-01-12 15:00:00', (string)$date);
    }

    public function testRecurrenceDSTSwitch()
    {
        // Recurring event starts 10/1/2011 15:00:00 EDST
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Test Decoding
        $stream = fopen(__DIR__ . '/fixtures/dst.wbxml', 'r+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream);

        $element = $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $appt = new Horde_ActiveSync_Message_Appointment(array('logger' => $logger));
        $appt->decodeStream($decoder);
        fclose($stream);
        $decoder->getElementEndTag();
        $rrule = $appt->getRecurrence();

        // Get the next recurrence, still during EDST
        $next = $rrule->nextActiveRecurrence(new Horde_Date('2011-10-15'));
        $this->assertEquals('2011-10-15 15:00:00', (string)$next->setTimezone('America/New_York'));

        // Now get an occurence after the transition to EST.
        $next = $rrule->nextActiveRecurrence(new Horde_Date('2011-12-01'));
        $this->assertEquals('2011-12-10 15:00:00', (string)$next->setTimezone('America/New_York'));
    }

}
