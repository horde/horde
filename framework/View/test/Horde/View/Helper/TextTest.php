<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
        $this->view = new Horde_View();
        $this->view->addHelper('Text');
    }

    // test escaping data
    public function testEscape()
    {
        $text = "Test 'escaping html' \"quotes\" and & amps";
        $expected = "Test &#039;escaping html&#039; &quot;quotes&quot; and &amp; amps";
        $this->assertEquals($expected, $this->view->h($text));
    }

    // test truncate
    public function testTruncate()
    {
        $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';
        $expected = 'The quick brown fox jumps over the la...';
        $this->assertEquals($expected, $this->view->truncate($str, 40));
    }

    // test truncate
    public function testTruncateMiddle()
    {
        $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';
        $expected = 'The quick brown fox... tomorrow morning.';
        $this->assertEquals($expected, $this->view->truncateMiddle($str, 40));
    }

    // text too short to trucate
    public function testTruncateMiddleTooShort()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick brown fox jumps over the dog.';
        $this->assertEquals($expected, $this->view->truncateMiddle($str, 40));
    }


    // test highlighter
    public function testHighlightDefault()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick <strong class="highlight">brown</strong> fox jumps over the dog.';
        $this->assertEquals($expected, $this->view->highlight($str, 'brown'));
    }

    // test failure to highlight
    public function testHighlightCustom()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $expected = 'The quick <em>brown</em> fox jumps over the dog.';
        $this->assertEquals($expected, $this->view->highlight($str, 'brown', '<em>$1</em>'));
    }

    // test failure to highlight
    public function testHighlightNoMatch()
    {
        $str = 'The quick brown fox jumps over the dog.';
        $this->assertEquals($str, $this->view->highlight($str, 'black'));
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
        $this->assertEquals('even', (string)$this->view->cycle('even', 'odd'));
        $this->assertEquals('odd',  (string)$this->view->cycle('even', 'odd'));
        $this->assertEquals('even', (string)$this->view->cycle('even', 'odd'));
        $this->assertEquals('1',    (string)$this->view->cycle(1, 2, 3));
        $this->assertEquals('2',    (string)$this->view->cycle(1, 2, 3));
        $this->assertEquals('3',    (string)$this->view->cycle(1, 2, 3));
    }

    public function testNamedCycles()
    {
        $this->assertEquals('1',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('2',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('blue', (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('3',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
    }

    public function testDefaultNamedCycle()
    {
        $this->assertEquals('1', (string)$this->view->cycle(1, 2, 3));
        $this->assertEquals('2', (string)$this->view->cycle(1, 2, 3, array('name' => 'default')));
        $this->assertEquals('3', (string)$this->view->cycle(1, 2, 3));
    }

    public function testResetCycle()
    {
        $this->assertEquals('1', (string)$this->view->cycle(1, 2, 3));
        $this->assertEquals('2', (string)$this->view->cycle(1, 2, 3));
        $this->view->resetCycle();
        $this->assertEquals('1', (string)$this->view->cycle(1, 2, 3));
    }

    public function testResetUnknownCycle()
    {
        $this->view->resetCycle('colors');
    }

    public function testResetNamedCycle()
    {
        $this->assertEquals('1',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
        $this->view->resetCycle('numbers');
        $this->assertEquals('1',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('blue', (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
        $this->assertEquals('2',    (string)$this->view->cycle(1, 2, 3, array('name' => 'numbers')));
        $this->assertEquals('red',  (string)$this->view->cycle('red', 'blue', array('name' => 'colors')));
    }

    public function testPluralization()
    {
        $this->assertEquals('1 count',  $this->view->pluralize(1, 'count'));
        $this->assertEquals('2 counts', $this->view->pluralize(2, 'count'));
        $this->assertEquals('1 count',  $this->view->pluralize('1', 'count'));
        $this->assertEquals('2 counts', $this->view->pluralize('2', 'count'));
        $this->assertEquals('1,066 counts', $this->view->pluralize('1,066', 'count'));
        $this->assertEquals('1.25 counts',  $this->view->pluralize('1.25', 'count'));
        $this->assertEquals('2 counters',   $this->view->pluralize('2', 'count', 'counters'));
    }

}
