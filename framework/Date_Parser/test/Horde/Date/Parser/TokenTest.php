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
class Horde_Date_Parser_TokenTest extends PHPUnit_Framework_TestCase
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
        $this->assertType('string', $token->getTag('foo'));

        $token->tag('bar', 5);
        $this->assertEquals(2, count($token->tags));
        $this->assertType('int', $token->getTag('bar'));

        $token->untag('foo');
        $this->assertEquals(1, count($token->tags));
        $this->assertType('int', $token->getTag('bar'));
    }

}
