<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_RecurrenceTest extends PHPUnit_Framework_TestCase
{
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

/*
function recur($r)
{
    $ical = new Horde_iCalendar();
    echo $r->toRRule10($ical) . "\n";
    echo $r->toRRule20($ical) . "\n";
    $protect = 0;
    $next = new Horde_Date('2007-03-01 00:00:00');
    while ($next = $r->nextRecurrence($next)) {
        if (++$protect > 10) {
            die('Infinite loop');
        }
        echo (string)$next . "\n";
        $next->mday++;
    }
    var_dump($next);
    echo "\n";
}

function dump($rrule, $version)
{
    $r = new Horde_Date_Recurrence('2007-03-01 10:00:00');
    if ($version == 1) {
        $r->fromRRule10($rrule);
    } else {
        $r->fromRRule20($rrule);
    }

    var_dump($r->getRecurType());
    var_dump((int)$r->getRecurInterval());
    var_dump($r->getRecurOnDays());
    var_dump($r->getRecurCount());
    if ($r->hasRecurEnd()) {
        echo (string)$r->recurEnd . "\n";
    }
    echo "\n";
}

$r = new Horde_Date_Recurrence('2007-03-01 10:00:00');

$r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
$r->setRecurInterval(2);
$r->setRecurEnd(new Horde_Date('2007-03-07 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
$r->setRecurOnDay(Horde_Date::MASK_THURSDAY);
$r->setRecurInterval(1);
$r->setRecurEnd(new Horde_Date('2007-03-29 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);
$r->setRecurInterval(2);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
$r->setRecurInterval(1);
$r->setRecurEnd(new Horde_Date('2007-05-01 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);
$r->setRecurInterval(2);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
$r->setRecurInterval(1);
$r->setRecurEnd(new Horde_Date('2007-05-01 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
$r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
$r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);

$r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
$r->setRecurEnd(new Horde_Date('2009-03-01 10:00:00'));
recur($r);
$r->setRecurCount(4);
recur($r);

$r = new Horde_Date_Recurrence('2007-04-25 12:00:00');
$r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
$r->setRecurEnd(new Horde_Date('2011-04-25 23:00:00'));
$r->setRecurInterval(2);
$next = new Horde_Date('2009-03-30 00:00:00');
$next = $r->nextRecurrence($next);
echo (string)$next . "\n\n";

$r = new Horde_Date_Recurrence('2008-02-29 00:00:00');
$r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
$r->setRecurInterval(1);
$next = new Horde_Date('2008-03-01 00:00:00');
$next = $r->nextRecurrence($next);
echo (string)$next . "\n\n";

$r = new Horde_Date_Recurrence('2008-03-14 12:00:00');
$r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
$r->setRecurCount(2);
$ical = new Horde_iCalendar();
echo $r->toRRule10($ical) . "\n";
echo $r->toRRule20($ical) . "\n\n";

$rrule1 = array('D2 20070307',
                'D2 20070308T090000Z',
                'D2 #4',
                'W1 TH 20070329',
                'W1 TH 20070330T080000Z',
                'W1 SU MO TU WE TH FR SA 20070603T235959',
                'W1 TH #4',
                'W2 TH #4',
                'MD1 1 20070501',
                'MD1 1 20070502T080000Z',
                'MD1 1 #4',
                'MD2 1 #4',
                'MP1 1+ TH 20070501',
                'MP1 1+ TH 20070502T080000Z',
                'MP1 1+ TH #4',
                'YM1 3 20090301',
                'YM1 3 20090302T090000Z',
                'YM1 3 #4',
                'YD1 60 20090301',
                'YD1 60 20090302T090000Z',
                'YD1 60 #4');
foreach ($rrule1 as $rrule) {
    dump($rrule, 1);
}
$rrule2 = array('FREQ=DAILY;INTERVAL=2;UNTIL=20070307',
                'FREQ=DAILY;INTERVAL=2;UNTIL=20070308T090000Z',
                'FREQ=DAILY;INTERVAL=2;COUNT=4',
                'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070329',
                'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070330T080000Z',
                'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=4',
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;COUNT=4',
                'FREQ=MONTHLY;INTERVAL=1;UNTIL=20070501',
                'FREQ=MONTHLY;INTERVAL=1;UNTIL=20070502T080000Z',
                'FREQ=MONTHLY;INTERVAL=1;COUNT=4',
                'FREQ=MONTHLY;INTERVAL=2;COUNT=4',
                'FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070501',
                'FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070502T080000Z',
                'FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;COUNT=4',
                'FREQ=YEARLY;INTERVAL=1;UNTIL=20090301',
                'FREQ=YEARLY;INTERVAL=1;UNTIL=20090302T090000Z',
                'FREQ=YEARLY;INTERVAL=1;COUNT=4',
                'FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090301',
                'FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090302T090000Z',
                'FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;COUNT=4',
                'FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090301',
                'FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090302T090000Z',
                'FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;COUNT=4');
foreach ($rrule2 as $rrule) {
    dump($rrule, 2);
}

?>
--EXPECT--
D2 20070308T090000Z
FREQ=DAILY;INTERVAL=2;UNTIL=20070308T090000Z
2007-03-01 10:00:00
2007-03-03 10:00:00
2007-03-05 10:00:00
2007-03-07 10:00:00
bool(false)

D2 #4
FREQ=DAILY;INTERVAL=2;COUNT=4
2007-03-01 10:00:00
2007-03-03 10:00:00
2007-03-05 10:00:00
2007-03-07 10:00:00
bool(false)

W1 TH 20070330T080000Z
FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20070330T080000Z
2007-03-01 10:00:00
2007-03-08 10:00:00
2007-03-15 10:00:00
2007-03-22 10:00:00
2007-03-29 10:00:00
bool(false)

W1 TH #4
FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=4
2007-03-01 10:00:00
2007-03-08 10:00:00
2007-03-15 10:00:00
2007-03-22 10:00:00
bool(false)

W2 TH #4
FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;COUNT=4
2007-03-01 10:00:00
2007-03-15 10:00:00
2007-03-29 10:00:00
2007-04-12 10:00:00
bool(false)

MD1 1 20070502T080000Z
FREQ=MONTHLY;INTERVAL=1;UNTIL=20070502T080000Z
2007-03-01 10:00:00
2007-04-01 10:00:00
2007-05-01 10:00:00
bool(false)

MD1 1 #4
FREQ=MONTHLY;INTERVAL=1;COUNT=4
2007-03-01 10:00:00
2007-04-01 10:00:00
2007-05-01 10:00:00
2007-06-01 10:00:00
bool(false)

MD2 1 #4
FREQ=MONTHLY;INTERVAL=2;COUNT=4
2007-03-01 10:00:00
2007-05-01 10:00:00
2007-07-01 10:00:00
2007-09-01 10:00:00
bool(false)

MP1 1+ TH 20070502T080000Z
FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;UNTIL=20070502T080000Z
2007-03-01 10:00:00
2007-04-05 10:00:00
bool(false)

MP1 1+ TH #4
FREQ=MONTHLY;INTERVAL=1;BYDAY=1TH;COUNT=4
2007-03-01 10:00:00
2007-04-05 10:00:00
2007-05-03 10:00:00
2007-06-07 10:00:00
bool(false)

YM1 3 20090302T090000Z
FREQ=YEARLY;INTERVAL=1;UNTIL=20090302T090000Z
2007-03-01 10:00:00
2008-03-01 10:00:00
2009-03-01 10:00:00
bool(false)

YM1 3 #4
FREQ=YEARLY;INTERVAL=1;COUNT=4
2007-03-01 10:00:00
2008-03-01 10:00:00
2009-03-01 10:00:00
2010-03-01 10:00:00
bool(false)

YD1 60 20090302T090000Z
FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;UNTIL=20090302T090000Z
2007-03-01 10:00:00
2008-02-29 10:00:00
2009-03-01 10:00:00
bool(false)

YD1 60 #4
FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60;COUNT=4
2007-03-01 10:00:00
2008-02-29 10:00:00
2009-03-01 10:00:00
2010-03-01 10:00:00
bool(false)


FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;UNTIL=20090302T090000Z
2007-03-01 10:00:00
2008-03-06 10:00:00
bool(false)


FREQ=YEARLY;INTERVAL=1;BYDAY=1TH;BYMONTH=3;COUNT=4
2007-03-01 10:00:00
2008-03-06 10:00:00
2009-03-05 10:00:00
2010-03-04 10:00:00
bool(false)

2009-04-25 12:00:00

2012-02-29 00:00:00

MP1 2+ FR #2
FREQ=MONTHLY;INTERVAL=1;BYDAY=2FR;COUNT=2

int(1)
int(2)
NULL
NULL
2007-03-07 00:00:00

int(1)
int(2)
NULL
NULL
2007-03-08 00:00:00

int(1)
int(2)
NULL
int(4)

int(2)
int(1)
int(16)
NULL
2007-03-29 00:00:00

int(2)
int(1)
int(16)
NULL
2007-03-30 00:00:00

int(2)
int(1)
int(127)
NULL
2007-06-03 00:00:00

int(2)
int(1)
int(16)
int(4)

int(2)
int(2)
int(16)
int(4)

int(3)
int(1)
NULL
NULL
2007-05-01 00:00:00

int(3)
int(1)
NULL
NULL
2007-05-02 00:00:00

int(3)
int(1)
NULL
int(4)

int(3)
int(2)
NULL
int(4)

int(4)
int(1)
NULL
NULL
2007-05-01 00:00:00

int(4)
int(1)
NULL
NULL
2007-05-02 00:00:00

int(4)
int(1)
NULL
int(4)

int(5)
int(1)
NULL
NULL
2009-03-01 00:00:00

int(5)
int(1)
NULL
NULL
2009-03-02 00:00:00

int(5)
int(1)
NULL
int(4)

int(6)
int(1)
NULL
NULL
2009-03-01 00:00:00

int(6)
int(1)
NULL
NULL
2009-03-02 00:00:00

int(6)
int(1)
NULL
int(4)

int(1)
int(2)
NULL
NULL
2007-03-07 00:00:00

int(1)
int(2)
NULL
NULL
2007-03-08 00:00:00

int(1)
int(2)
NULL
int(4)

int(2)
int(1)
int(16)
NULL
2007-03-29 00:00:00

int(2)
int(1)
int(16)
NULL
2007-03-30 00:00:00

int(2)
int(1)
int(16)
int(4)

int(2)
int(2)
int(16)
int(4)

int(3)
int(1)
NULL
NULL
2007-05-01 00:00:00

int(3)
int(1)
NULL
NULL
2007-05-02 00:00:00

int(3)
int(1)
NULL
int(4)

int(3)
int(2)
NULL
int(4)

int(4)
int(1)
NULL
NULL
2007-05-01 00:00:00

int(4)
int(1)
NULL
NULL
2007-05-02 00:00:00

int(4)
int(1)
NULL
int(4)

int(5)
int(1)
NULL
NULL
2009-03-01 00:00:00

int(5)
int(1)
NULL
NULL
2009-03-02 00:00:00

int(5)
int(1)
NULL
int(4)

int(6)
int(1)
NULL
NULL
2009-03-01 00:00:00

int(6)
int(1)
NULL
NULL
2009-03-02 00:00:00

int(6)
int(1)
NULL
int(4)

int(7)
int(1)
NULL
NULL
2009-03-01 00:00:00

int(7)
int(1)
NULL
NULL
2009-03-02 00:00:00

int(7)
int(1)
NULL
int(4)
*/
