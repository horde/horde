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
}
