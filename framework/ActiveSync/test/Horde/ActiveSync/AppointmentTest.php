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
        $this->markTestSkipped('Needs updated fixture.');
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

        // TODO
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
        $this->markTestSkipped('Needs updated fixture.');
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
        $this->markTestSkipped('Needs updated fixture.');
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

    public function testAlldayEncoding()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Check that the encoded wbxml looks correct.
        $stream_out = fopen('php://memory', 'w+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($stream_out);
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $message->setSubject('Test Event');
        $message->alldayevent = true;
        $start = new Horde_Date('1970-03-20T00:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T00:00:00', 'America/New_York');
        $message->starttime = $start;
        $message->endtime = $end;
        $message->setTimezone($start);
        $message->encodeStream($encoder);

        $fixture = file_get_contents(__DIR__ . '/fixtures/allday_appointment.wbxml');
        rewind($stream_out);
        $this->assertEquals($fixture, stream_get_contents($stream_out));

        // Make sure EAS versions work properly.
        rewind($stream_out);
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream_out);
        $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $message->decodeStream($decoder);
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $start->setTimezone('America/New_York');
        $this->assertEquals('1970-03-21 00:00:00', (string)$end);
        $this->assertEquals('1970-03-20 00:00:00', (string)$start);

        rewind($stream_out);
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN)
        );
        $decoder = new Horde_ActiveSync_Wbxml_Decoder($stream_out);
        $decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA);
        $message->decodeStream($decoder);
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $start->setTimezone('America/New_York');
        $this->assertEquals('1970-03-21 00:00:00', (string)$end);
        $this->assertEquals('1970-03-20 00:00:00', (string)$start);
    }

    /**
     * Test deprecated setDatetime method since it's still used in FW_52.
     */
    public function testSetDatetimeAlldayHandling()
    {
        $l = new Horde_Test_Log();
        $logger = $l->getLogger();

        // Test the deprecated setDatetime method's ability to properly detect
        // and set properties.
        // Single day 00:00 to 00:00
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T00:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T00:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'end' => $end));
        $this->assertEquals(true, $message->alldayevent);

        // Multiday 00:00 to 23:59
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T00:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T23:59:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'end' => $end));
        $this->assertEquals(true, $message->alldayevent);
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $this->assertEquals('1970-03-22 00:00:00', (string)$end);

        // Single day with incorrect time part, no endtime given.
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T04:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'allday' => true));
        $this->assertEquals(true, $message->alldayevent);
        $start = $message->starttime;
        $start->setTimezone('America/New_York');
        $this->assertEquals('1970-03-20 00:00:00', (string)$start);
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $this->assertEquals('1970-03-21 00:00:00', (string)$end);

        // Single day, no endtime given.
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T00:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'allday' => true));
        $this->assertEquals(true, $message->alldayevent);
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $this->assertEquals('1970-03-21 00:00:00', (string)$end);

        // Make sure non-all day events don't inadvertently get converted to one
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T05:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T00:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'end' => $end));
        $this->assertEquals(false, $message->alldayevent);
        $start = $message->starttime;
        $start->setTimezone('America/New_York');
        $this->assertEquals('1970-03-20 05:00:00', (string)$start);

        // Incorrect timeparts given, but allday flag is set.
        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T00:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T05:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'end' => $end, 'allday' => true));
        $this->assertEquals(true, $message->alldayevent);
        $start = $message->starttime;
        $start->setTimezone('America/New_York');
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $this->assertEquals('1970-03-20 00:00:00', (string)$start);
        $this->assertEquals('1970-03-22 00:00:00', (string)$end);

        $message = new Horde_ActiveSync_Message_Appointment(
            array('logger' => $logger, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN)
        );
        $start = new Horde_Date('1970-03-20T08:00:00', 'America/New_York');
        $end = new Horde_Date('1970-03-21T05:00:00', 'America/New_York');
        $message->setDatetime(array('start' => $start, 'end' => $end, 'allday' => true));
        $this->assertEquals(true, $message->alldayevent);
        $start = $message->starttime;
        $start->setTimezone('America/New_York');
        $end = $message->endtime;
        $end->setTimezone('America/New_York');
        $this->assertEquals('1970-03-20 00:00:00', (string)$start);
        $this->assertEquals('1970-03-22 00:00:00', (string)$end);
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

    public function testMissingSupportedTag()
    {
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $fixture = array(
            'userAgent' => 'Apple-iPad3C6/1202.435',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 8.1.1')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $contact = new Horde_ActiveSync_Message_Appointment(array('device' => $device, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN));
        $contact->setSupported(array());
        $this->assertEquals(false, $contact->isGhosted('subject'));
        $this->assertEquals(false, $contact->isGhosted('body'));

        $device = new Horde_ActiveSync_Device($state, $fixture);
        $contact = new Horde_ActiveSync_Message_Appointment(array('device' => $device, 'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN));
        $contact->setSupported(array());
        $this->assertEquals(true, $contact->isGhosted('subject'));
        $this->assertEquals(true, $contact->isGhosted('body'));
    }

    public function testEmptySupportedTag()
    {
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        $fixture = array(
            'userAgent' => 'Apple-iPad3C6/1202.435',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 8.1.1')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $contact = new Horde_ActiveSync_Message_Appointment(array('device' => $device, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN));
        $contact->setSupported(array(Horde_ActiveSync::ALL_GHOSTED));
        $this->assertEquals(true, $contact->isGhosted('subject'));
        $this->assertEquals(true, $contact->isGhosted('body'));

        $device = new Horde_ActiveSync_Device($state, $fixture);
        $contact = new Horde_ActiveSync_Message_Appointment(array('device' => $device, 'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN));
        $contact->setSupported(array(Horde_ActiveSync::ALL_GHOSTED));
        $this->assertEquals(true, $contact->isGhosted('subject'));
        $this->assertEquals(true, $contact->isGhosted('body'));
    }

}
