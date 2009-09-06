<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_RecurrenceTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Horde_String::setDefaultCharset('UTF-8');
        $this->ical = new Horde_iCalendar();
    }

    private function _getRecurrences($r)
    {
        $recurrences = array();
        $protect = 0;
        // This is a Thursday
        $next = new Horde_Date('2007-03-01 00:00:00');
        while ($next = $r->nextRecurrence($next)) {
            if (++$protect > 10) {
                return 'Infinite loop';
            }
            $recurrences[] = (string)$next;
            $next->mday++;
        }
        return $recurrences;
    }

    public function testDailyEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $r->setRecurEnd(new Horde_Date('2007-03-07 10:00:00'));
        $this->assertEquals('D2 20070308T090000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=DAILY;INTERVAL=2;UNTIL=20070308T090000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-03-03 10:00:00',
                                  '2007-03-05 10:00:00',
                                  '2007-03-07 10:00:00'),
                            $this->_getRecurrences($r));
        $r->setRecurCount(4);
    }

    public function testDailyCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $r->setRecurCount(4);
        $this->assertEquals('D2 #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=DAILY;INTERVAL=2;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-03-03 10:00:00',
                                  '2007-03-05 10:00:00',
                                  '2007-03-07 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testWeeklyEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurOnDay(Horde_Date::MASK_THURSDAY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2007-03-29 10:00:00'));
        $this->assertEquals('W1 TH 20070330T080000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070330T080000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-03-08 10:00:00',
                                  '2007-03-15 10:00:00',
                                  '2007-03-22 10:00:00',
                                  '2007-03-29 10:00:00'),
                            $this->_getRecurrences($r));

        $r = new Horde_Date_Recurrence('2009-09-28 08:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_TUESDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_THURSDAY | Horde_Date::MASK_FRIDAY);
        $r->setRecurInterval(7);
        $r->setRecurEnd(new Horde_Date('2010-02-05 00:00:00'));
        $this->assertEquals(array(
                                '2009-09-28 08:00:00',
                                '2009-09-29 08:00:00',
                                '2009-09-30 08:00:00',
                                '2009-10-01 08:00:00',
                                '2009-10-02 08:00:00',
                                '2009-11-16 08:00:00',
                                '2009-11-17 08:00:00',
                                '2009-11-18 08:00:00',
                                '2009-11-19 08:00:00',
                                '2009-11-20 08:00:00',
                                '2010-01-04 08:00:00',
                                '2010-01-05 08:00:00',
                                '2010-01-06 08:00:00',
                                '2010-01-07 08:00:00',
                                '2010-01-08 08:00:00',
),
                            $this->_getRecurrences($r));
    }

    public function testWeeklyCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurOnDay(Horde_Date::MASK_THURSDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(4);
        $this->assertEquals('W1 TH #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-03-08 10:00:00',
                                  '2007-03-15 10:00:00',
                                  '2007-03-22 10:00:00'),
                            $this->_getRecurrences($r));
        $r->setRecurInterval(2);
        $this->assertEquals('W2 TH #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-03-15 10:00:00',
                                  '2007-03-29 10:00:00',
                                  '2007-04-12 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testMonthlyEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2007-05-01 10:00:00'));
        $this->assertEquals('MD1 1 20070502T080000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;UNTIL=20070502T080000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-04-01 10:00:00',
                                  '2007-05-01 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testMonthlyCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurCount(4);
        $this->assertEquals('MD1 1 #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-04-01 10:00:00',
                                  '2007-05-01 10:00:00',
                                  '2007-06-01 10:00:00'),
                            $this->_getRecurrences($r));
        $r->setRecurInterval(2);
        $this->assertEquals('MD2 1 #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=2;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-05-01 10:00:00',
                                  '2007-07-01 10:00:00',
                                  '2007-09-01 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testMonthlyDayEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2007-05-01 10:00:00'));
        $this->assertEquals('MP1 1+ TH 20070502T080000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070502T080000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-04-05 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testMonthlyDayCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(4);
        $this->assertEquals('MP1 1+ TH #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2007-04-05 10:00:00',
                                  '2007-05-03 10:00:00',
                                  '2007-06-07 10:00:00'),
                            $this->_getRecurrences($r));

        $r = new Horde_Date_Recurrence('2008-03-14 12:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurCount(2);
        $this->assertEquals('MP1 2+ FR #2', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;BYDAY=2FR;COUNT=2', $r->toRRule20($this->ical));
    }

    public function testYearlyDateEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
        $this->assertEquals('YM1 3 20090302T090000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;UNTIL=20090302T090000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-03-01 10:00:00',
                                  '2009-03-01 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testYearlyDateCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurCount(4);
        $this->assertEquals('YM1 3 #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-03-01 10:00:00',
                                  '2009-03-01 10:00:00',
                                  '2010-03-01 10:00:00'),
                            $this->_getRecurrences($r));

        $r = new Horde_Date_Recurrence('2007-04-25 12:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurEnd(new Horde_Date('2011-04-25 23:00:00'));
        $r->setRecurInterval(2);
        $this->assertEquals('2009-04-25 12:00:00', (string)$r->nextRecurrence(new Horde_Date('2009-03-30 00:00:00')));

        $r = new Horde_Date_Recurrence('2008-02-29 00:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);
        $this->assertEquals('2012-02-29 00:00:00', (string)$r->nextRecurrence(new Horde_Date('2008-03-01 00:00:00')));
    }

    public function testYearlyDayEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
        $this->assertEquals('YD1 60 20090302T090000Z', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090302T090000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-02-29 10:00:00',
                                  '2009-03-01 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testYearlyDayCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurCount(4);
        $this->assertEquals('YD1 60 #4', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-02-29 10:00:00',
                                  '2009-03-01 10:00:00',
                                  '2010-03-01 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testYearlyWeekEnd()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
        $this->assertEquals('', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090302T090000Z', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-03-06 10:00:00'),
                            $this->_getRecurrences($r));
    }

    public function testYearlyWeekCount()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurCount(4);
        $this->assertEquals('', $r->toRRule10($this->ical));
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;COUNT=4', $r->toRRule20($this->ical));
        $this->assertEquals(array('2007-03-01 10:00:00',
                                  '2008-03-06 10:00:00',
                                  '2009-03-05 10:00:00',
                                  '2010-03-04 10:00:00'),
                            $this->_getRecurrences($r));

        $r = new Horde_Date_Recurrence('2009-03-27 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurCount(1);
        $this->assertEquals('FREQ=YEARLY;INTERVAL=1;BYDAY=4FR;BYMONTH=3;COUNT=1', $r->toRRule20($this->ical));
    }

    public function testParseDaily()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('D2 20070307');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-07 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('D2 20070308T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-08 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('D2 #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=DAILY;INTERVAL=2;UNTIL=20070307');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-07 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=DAILY;INTERVAL=2;UNTIL=20070308T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-08 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=DAILY;INTERVAL=2;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_DAILY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseWeekly()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('W1 TH 20070329');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-29 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('W1 TH 20070330T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-30 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('W1 SU MO TU WE TH FR SA 20070603T235959');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_ALLDAYS, $r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-06-03 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('W1 TH #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule10('W2 TH #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070329');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-29 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070330T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-03-30 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertEquals(Horde_Date::MASK_THURSDAY, $r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseMonthlyDate()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('MD1 1 20070501');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('MD1 1 20070502T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('MD1 1 #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule10('MD2 1 #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;UNTIL=20070501');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;UNTIL=20070502T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=2;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r->getRecurType());
        $this->assertEquals(2, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseMonthlyWeekday()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('MP1 1+ TH 20070501');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('MP1 1+ TH 20070502T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('MP1 1+ TH #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070501');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070502T080000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2007-05-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseYearlyDate()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('YM1 3 20090301');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('YM1 3 20090302T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('YM1 3 #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;UNTIL=20090301');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;UNTIL=20090302T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseYearlyDay()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule10('YD1 60 20090301');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('YD1 60 20090302T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule10('YD1 60 #4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090301');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090302T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testParseYearlyWeekday()
    {
        $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090301');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-01 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090302T090000Z');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertNull($r->getRecurCount());
        $this->assertEquals('2009-03-02 00:00:00', (string)$r->recurEnd);

        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;COUNT=4');
        $this->assertEquals(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $r->getRecurType());
        $this->assertEquals(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurOnDays());
        $this->assertEquals(4, $r->getRecurCount());
    }

    public function testHash()
    {
        $d = new Horde_Date(1970, 1, 1);
        $r = new Horde_Date_Recurrence(new Horde_Date(1970, 1, 1));
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addException(1970, 1, 1);
        $r->addException(1970, 1, 3);
        $r->addException(1970, 1, 4);
        $r->setRecurEnd(new Horde_Date(1970, 1, 4));

        $s = new Horde_Date_Recurrence(new Horde_Date(1970, 1, 1));
        $s->fromHash($r->toHash());
        $this->assertTrue($s->hasRecurEnd());

        $next = $s->nextRecurrence(new Horde_Date($s->start));
        $this->assertEquals(1, $next->mday);
        $this->assertTrue($s->hasException($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertFalse($s->hasException($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertTrue($s->hasException($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertTrue($s->hasException($next->year, $next->month, $next->mday));

        $this->assertEquals(3, count($s->getExceptions()));
        $this->assertTrue($s->hasActiveRecurrence());

        $s->addException(1970, 1, 2);
        $this->assertFalse($s->hasActiveRecurrence());
    }

    /**
     */
    public function testCompletions()
    {
        $r = new Horde_Date_Recurrence(new Horde_Date(1970, 1, 1));
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addCompletion(1970, 1, 2);
        $this->assertTrue($r->hasCompletion(1970, 1, 2));
        $this->assertEquals(1, count($r->getCompletions()));

        $r->addCompletion(1970, 1, 4);
        $this->assertEquals(2, count($r->getCompletions()));

        $r->deleteCompletion(1970, 1, 2);
        $this->assertEquals(1, count($r->getCompletions()));
        $this->assertFalse($r->hasCompletion(1970, 1, 2));

        $r->addCompletion(1970, 1, 2);
        $r->addException(1970, 1, 1);
        $r->addException(1970, 1, 3);

        $next = $r->nextRecurrence(new Horde_Date($r->start));
        $this->assertEquals(1, $next->mday);
        $this->assertTrue($r->hasException($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $r->nextRecurrence($next);
        $this->assertTrue($r->hasCompletion($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $r->nextRecurrence($next);
        $this->assertTrue($r->hasException($next->year, $next->month, $next->mday));

        $next->mday++;
        $next = $r->nextRecurrence($next);
        $this->assertTrue($r->hasCompletion($next->year, $next->month, $next->mday));

        $r->setRecurEnd(new Horde_Date(1970, 1, 4));
        $this->assertTrue($r->hasRecurEnd());
        $this->assertFalse($r->hasActiveRecurrence());

        $s = new Horde_Date_Recurrence(new Horde_Date(1970, 1, 1));
        $s->fromHash($r->toHash());
        $this->assertTrue($s->hasRecurEnd());

        $next = $s->nextRecurrence(new Horde_Date($s->start));
        $this->assertEquals(1, $next->mday);
        $this->assertTrue($s->hasException($next->year, $next->month, $next->mday));
        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertTrue($s->hasCompletion($next->year, $next->month, $next->mday));
        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertTrue($s->hasException($next->year, $next->month, $next->mday));
        $next->mday++;
        $next = $s->nextRecurrence($next);
        $this->assertTrue($s->hasCompletion($next->year, $next->month, $next->mday));

        $this->assertEquals(2, count($s->getCompletions()));
        $this->assertEquals(2, count($s->getExceptions()));
        $this->assertFalse($s->hasActiveRecurrence());

        $this->assertEquals(2, count($s->getCompletions()));
        $s->deleteCompletion(1970, 1, 2);
        $this->assertEquals(1, count($s->getCompletions()));
        $s->deleteCompletion(1970, 1, 4);
        $this->assertEquals(0, count($s->getCompletions()));
    }

    public function testBug2813RecurrenceEndFromIcalendar()
    {
        $iCal = new Horde_iCalendar();
        $iCal->parsevCalendar(file_get_contents(dirname(__FILE__) . '/fixtures/bug2813.ics'));
        $components = $iCal->getComponents();

        date_default_timezone_set('US/Eastern');

        foreach ($components as $content) {
            if ($content instanceof Horde_iCalendar_vevent) {
                $start = new Horde_Date($content->getAttribute('DTSTART'));
                $end = new Horde_Date($content->getAttribute('DTEND'));
                $rrule = $content->getAttribute('RRULE');
                $recurrence = new Horde_Date_Recurrence($start, $end);
                $recurrence->fromRRule20($rrule);
                break;
            }
        }

        $after = array('year' => 2006, 'month' => 6);

        $after['mday'] = 16;
        $this->assertEquals('2006-06-16 18:00:00', (string)$recurrence->nextRecurrence($after));

        $after['mday'] = 17;
        $this->assertEquals('2006-06-17 18:00:00', (string)$recurrence->nextRecurrence($after));

        $after['mday'] = 18;
        $this->assertEquals('', (string)$recurrence->nextRecurrence($after));
    }

    public function testBug4626MonthlyByDayRRule()
    {
        $rrule = new Horde_Date_Recurrence('2008-04-05 00:00:00');
        $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $rrule->setRecurOnDay(Horde_Date::MASK_SATURDAY);

        $this->assertEquals('MP1 1+ SA #0', $rrule->toRRule10(new Horde_iCalendar()));
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=1;BYDAY=1SA', $rrule->toRRule20(new Horde_iCalendar()));
    }

}
