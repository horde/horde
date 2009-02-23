<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_TextTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->helper = new Horde_View_Helper_Text(new Horde_View());
    }

    // test escaping data
    public function testEscape()
    {
        $text = "Test 'escaping html' \"quotes\" and & amps";
        $expected = "Test &#039;escaping html&#039; &quot;quotes&quot; and &amp; amps";
        $this->assertEquals($expected, $this->helper->h($text));
    }

    // test truncate
    public function testTruncate()
    {
        $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';
        $expected = 'The quick brown fox jumps over the la...';
        $this->assertEquals($expected, $this->helper->truncate($str, 40));
    }

    // test truncate
    public function testTruncateMiddle()
    {
        $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';
        $expected = 'The quick brown fox... tomorrow morning.';
        $this->assertEquals($expected, $this->helper->truncateMiddle($str, 40));
    }

    // text too short to trucate
    public function testTruncateMiddleTooShort()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick brown fox jumps over the dog.';
        $this->assertEquals($expected, $this->helper->truncateMiddle($str, 40));
    }


    // test highlighter
    public function testHighlightDefault()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick <strong class="highlight">brown</strong> fox jumps over the dog.';
        $this->assertEquals($expected, $this->helper->highlight($str, 'brown'));
    }

    // test failure to highlight
    public function testHighlightCustom()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick <em>brown</em> fox jumps over the dog.';
        $this->assertEquals($expected, $this->helper->highlight($str, 'brown', '<em>$1</em>'));
    }

    // test failure to highlight
    public function testHighlightNoMatch()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $this->assertEquals($str, $this->helper->highlight($str, 'black'));
    }

    public function testCycleClass()
    {
        $value = new Horde_View_Helper_Text_Cycle(array('one', 2, '3'));

        $this->assertEquals('one', (string)$value);
        $this->assertEquals('2',   (string)$value);
        $this->assertEquals('3',   (string)$value);
        $this->assertEquals('one', (string)$value);
        $value->reset();
        $this->assertEquals('one', (string)$value);
        $this->assertEquals('2',   (string)$value);
        $this->assertEquals('3',   (string)$value);
    }

    public function testCycleClassWithInvalidArguments()
    {
        try {
            $value = new Horde_View_Helper_Text_Cycle('bad');
            $this->fail();
        } catch (InvalidArgumentException $e) {}

        try {
            $value = new Horde_View_Helper_Text_Cycle(array('foo'));
            $this->fail();
        } catch (InvalidArgumentException $e) {}

        try {
            $value = new Horde_View_Helper_Text_Cycle(array('foo', 'bar'), 'bad-arg');
            $this->fail();
        } catch (InvalidArgumentException $e) {}
    }

    public function testCycleResetsWithNewValues()
    {
        $this->assertEquals('even', (string)$this->helper->cycle('even', 'odd'));
        $this->assertEquals('odd',  (string)$this->helper->cycle('even', 'odd'));
        $this->assertEquals('even', (string)$this->helper->cycle('even', 'odd'));
        $this->assertEquals('1',    (string)$this->helper->cycle(1, 2, 3));
        $this->assertEquals('2',    (string)$this->helper->cycle(1, 2, 3));
        $this->assertEquals('3',    (string)$this->helper->cycle(1, 2, 3));
    }

    public function testNamedCycles()
    {
        $this->assertEquals('1',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('2',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('blue', (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('3',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
    }

    public function testDefaultNamedCycle()
    {
        $this->assertEquals('1', (string)$this->helper->cycle(1, 2, 3));
        $this->assertEquals('2', (string)$this->helper->cycle(1, 2, 3, array('name' => 'default')));
        $this->assertEquals('3', (string)$this->helper->cycle(1, 2, 3));
    }

    public function testResetCycle()
    {
        $this->assertEquals('1', (string)$this->helper->cycle(1, 2, 3));
        $this->assertEquals('2', (string)$this->helper->cycle(1, 2, 3));
        $this->helper->resetCycle();
        $this->assertEquals('1', (string)$this->helper->cycle(1, 2, 3));
    }

    public function testResetUnknownCycle()
    {
        $this->helper->resetCycle('colors');
    }

    public function testResetNamedCycle()
    {
        $this->assertEquals('1',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
        $this->helper->resetCycle('numbers');
        $this->assertEquals('1',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('blue', (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('2',    (string)$this->helper->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->helper->cycle('red', 'blue', array('name' => 'colors')));
    }

    public function testPluralization()
    {
        $this->assertEquals('1 count',  $this->helper->pluralize(1, 'count'));
        $this->assertEquals('2 counts', $this->helper->pluralize(2, 'count'));
        $this->assertEquals('1 count',  $this->helper->pluralize('1', 'count'));
        $this->assertEquals('2 counts', $this->helper->pluralize('2', 'count'));
        $this->assertEquals('1,066 counts', $this->helper->pluralize('1,066', 'count'));
        $this->assertEquals('1.25 counts',  $this->helper->pluralize('1.25', 'count'));
        $this->assertEquals('2 counters',   $this->helper->pluralize('2', 'count', 'counters'));
    }

}
