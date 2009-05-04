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
class Horde_Date_Parser_ParserTest extends Horde_Test_Case
{
    public function setUp()
    {
        // Wed Aug 16 14:00:00 UTC 2006
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testPostNormalizeAmPmAliases()
    {
        // affect wanted patterns
        $tokens = array(new Horde_Date_Parser_Token('5:00'), new Horde_Date_Parser_Token('morning'));
        $tokens[0]->tag('repeater_time', new Horde_Date_Repeater_Time('5:00'));
        $tokens[1]->tag('repeater_day_portion', new Horde_Date_Repeater_DayPortion('morning'));

        $this->assertEquals('morning', $tokens[1]->tags[0][1]->type);

        $parser = Horde_Date_Parser::factory();
        $tokens = $parser->dealiasAndDisambiguateTimes($tokens, array());

        $this->assertEquals('am', $tokens[1]->tags[0][1]->type);
        $this->assertEquals(2, count($tokens));

        // don't affect unwanted patterns
        $tokens = array(new Horde_Date_Parser_Token('friday'), new Horde_Date_Parser_Token('morning'));
        $tokens[0]->tag('repeater_day_name', 'friday');
        $tokens[1]->tag('repeater_day_portion', 'morning');

        $this->assertEquals('morning', $tokens[1]->tags[0][1]);

        $parser = Horde_Date_Parser::factory();
        $tokens = $parser->dealiasAndDisambiguateTimes($tokens, array());

        $this->assertEquals('morning', $tokens[1]->tags[0][1]);
        $this->assertEquals(2, count($tokens));
    }

    public function testGuess()
    {
        $parser = Horde_Date_Parser::factory();

        $span = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertEquals(new Horde_Date('2006-08-16 12:00:00'), $parser->guess($span));

        $span = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:01'));
        $this->assertEquals(new Horde_Date('2006-08-16 12:00:00'), $parser->guess($span));

        $span = new Horde_Date_Span(new Horde_Date('2006-11-01 00:00:00'), new Horde_Date('2006-12-01 00:00:00'));
        $this->assertEquals(new Horde_Date('2006-11-16 00:00:00'), $parser->guess($span));
    }

}
