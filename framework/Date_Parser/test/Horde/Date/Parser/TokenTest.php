<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class Horde_Date_Parser_TokenTest extends Horde_Test_Case
{
    public function testToken()
    {
        $token = new Horde_Date_Parser_Token('foo');
        $this->assertEquals('foo', $token->word);
        $this->assertEquals(0, count($token->tags));
        $this->assertFalse($token->tagged());

        $token->tag('foo', 'mytag');
        $this->assertEquals(1, count($token->tags));
        $this->assertTrue($token->tagged());
        $this->assertInternalType('string', $token->getTag('foo'));

        $token->tag('bar', 5);
        $this->assertEquals(2, count($token->tags));
        $this->assertInternalType('int', $token->getTag('bar'));

        $token->untag('foo');
        $this->assertEquals(1, count($token->tags));
        $this->assertInternalType('int', $token->getTag('bar'));
    }

    public function testScanForDayNames()
    {
        $parser = Horde_Date_Parser::factory();
        $tokenizer = $parser->componentFactory('Repeater');

        $token = new Horde_Date_Parser_Token('saturday');
        $repeater = $tokenizer->scanForDayNames($token);
        $this->assertInstanceOf('Horde_Date_Repeater_DayName', $repeater);
        $this->assertEquals('saturday', $repeater->type);

        $token = new Horde_Date_Parser_Token('sunday');
        $repeater = $tokenizer->scanForDayNames($token);
        $this->assertInstanceOf('Horde_Date_Repeater_DayName', $repeater);
        $this->assertEquals('sunday', $repeater->type);
    }

    public function testScanForTimezones()
    {
        $parser = Horde_Date_Parser::factory();
        $tokenizer = $parser->componentFactory('Timezone');

        $token = new Horde_Date_Parser_Token('9:00 est');
        $results = $tokenizer->scan(array($token));
        $this->assertEquals('tz', $results[0]->getTag('timezone'));

        $token = new Horde_Date_Parser_Token('this is test at 9est');
        $results = $tokenizer->scan(array($token));
        $this->assertEquals('tz', $results[0]->getTag('timezone'));

        $token = new Horde_Date_Parser_Token('this is test at 9 est');
        $results = $tokenizer->scan(array($token));
        $this->assertEquals('tz', $results[0]->getTag('timezone'));

        $token = new Horde_Date_Parser_Token('testing');
        $results = $tokenizer->scan(array($token));
        $this->assertEquals(null, $results[0]->getTag('timezone'));

        $token = new Horde_Date_Parser_Token('this is test');
        $results = $tokenizer->scan(array($token));
        $this->assertEquals(null, $results[0]->getTag('timezone'));
    }
}
