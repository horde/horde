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
class Horde_Date_Parser_Locale_DeTest extends Horde_Test_Case
{
    /**
     * Wed Aug 16 14:00:00 UTC 2006
     */
    public function setUp()
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
        $this->parser = Horde_Date_Parser::factory(array('locale' => 'de', 'now' => $this->now));
    }

    public function testTodayAt11()
    {
        $this->assertEquals('2006-08-16 11:00:00', (string)$this->parser->parse('heute um 11'));
    }

    public function testTomorrow()
    {
        $this->assertEquals('2006-08-17 09:00:00', (string)$this->parser->parse('morgen früh', array(), false));
    }

    public function testMorning()
    {
        $this->assertEquals('2006-08-16 09:00:00', (string)$this->parser->parse('heute morgen', array(), false));
    }

}
