<?php
/**
 * @package    Horde_Date
 * @subpackage UnitTests
 */

require_once 'PHPUnit/Framework.php';

require_once 'Horde/Date/Recurrence.php';

class Horde_Date_RecurrenceTest extends PHPUnit_Framework_TestCase
{
    /**
     */
    public function testHash()
    {
        $r = &new Horde_Date_Recurrence(0);
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addException(1970, 1, 1);
        $r->addException(1970, 1, 3);
        $r->addException(1970, 1, 4);

        $r->setRecurEnd(new Horde_Date(86400*3));

        $s = &new Horde_Date_Recurrence(0);
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
        $r = &new Horde_Date_Recurrence(0);
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

        $r->setRecurEnd(new Horde_Date(86400*3));
        $this->assertTrue($r->hasRecurEnd());

        $this->assertFalse($r->hasActiveRecurrence());

        $s = &new Horde_Date_Recurrence(0);
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
}
