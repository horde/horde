<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_Parser_ResultTest extends Horde_Test_Case
{
    public function testGuess()
    {
        $result = new Horde_Date_Parser_Result(null, null);

        $result->span = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertEquals(new Horde_Date('2006-08-16 12:00:00'), $result->guess());

        $result->span = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:01'));
        $this->assertEquals(new Horde_Date('2006-08-16 12:00:00'), $result->guess());

        $result->span = new Horde_Date_Span(new Horde_Date('2006-11-01 00:00:00'), new Horde_Date('2006-12-01 00:00:00'));
        $this->assertEquals(new Horde_Date('2006-11-16 00:00:00'), $result->guess());
    }

}
