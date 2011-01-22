<?php
/**
 * Json serialization tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Template
 * @subpackage UnitTests
 */

class Horde_Template_TemplateTest extends PHPUnit_Framework_TestCase
{
    // Associative Array Test
    public function testAssociativeArray()
    {
        $template = new Horde_Template();

        $template->set('foo', array('one' => 'one', 'two' => 2));

        $this->assertEquals(
            'one 2 ',
            $template->parse('<tag:foo.one /> <tag:foo.two /> <tag:foo />')
        );
    }

    // If Array Test
    public function testIfArray()
    {
        $template = new Horde_Template();

        $template->set('foo', array('one', 'two', 'three'), true);
        $template->set('bar', array(), true);

        $this->assertEquals(
            "one two three \nelse",
            $template->parse("<if:foo><loop:foo><tag:foo /> </loop:foo></if:foo>\n<if:bar><loop:bar><tag:bar /></loop:bar><else:bar>else</else:bar></if:bar>")
        );
    }

    // Simple Array Test
    public function testSimpleArray()
    {
        $template = new Horde_Template();

        $template->set('string', array('one', 'two', 'three'));
        $template->set('int', array(1, 2, 3));

        $this->assertEquals(
            "one two three \n1 2 3 ",
            $template->parse("<loop:string><tag:string /> </loop:string>\n<loop:int><tag:int /> </loop:int>")
        );
    }

    // Divider Test
    public function testDivider()
    {
        $template = new Horde_Template();

        $template->set('a', array('a', 'b', 'c', 'd'));

        $this->assertEquals(
            'a,b,c,d',
            $template->parse("<loop:a><divider:a>,</divider:a><tag:a /></loop:a>")
        );
    }

    // If/Else Test
    public function testIfElse()
    {
        $template = new Horde_Template();

        $template->set('foo', true, true);
        $template->set('bar', false, true);
        $template->set('baz', 'baz', true);

        $this->assertEquals(
            "foo\n\nfalse\nbaz",
            $template->parse("<if:foo>foo</if:foo>\n<if:bar>bar</if:bar>\n<if:bar>true<else:bar>false</else:bar></if:bar>\n<if:baz><tag:baz /></if:baz>")
        );
    }

    // Iterator Test
    public function testIterator()
    {
        $template = new Horde_Template();

        $s = array('one', 'two', 'three');
        $i = array(1, 2, 3);
        $a = array('one' => 'one', 'two' => 2);

        $template->set('s', $s);
        $template->set('i', $i);
        $template->set('a', $a);

        $this->assertEquals(
            "one,two,three,\n1,2,3,\none,2,",
            $template->parse("<loop:s><tag:s />,</loop:s>\n<loop:i><tag:i />,</loop:i>\n<tag:a.one />,<tag:a.two />,<tag:a />")
        );
    }

    // Scalar Test
    public function testScalar()
    {
        $template = new Horde_Template();

        $template->set('one', 'one');
        $template->set('two', 2);

        $this->assertEquals(
            "one\n2",
            $template->parse("<tag:one />\n<tag:two />")
        );
    }

}
