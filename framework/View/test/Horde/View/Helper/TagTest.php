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
class Horde_View_Helper_TagTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->view = new Horde_View();
        $this->view->addHelper('Tag');
    }

    public function testTag()
    {
        $this->assertEquals('<br>', $this->view->tag('br'));
        $this->assertEquals('<br clear="left">',
                            $this->view->tag('br', array('clear' => 'left')));
    }

    public function testTagOptions()
    {
        $this->assertRegExp('/\A<p class="(show|elsewhere)">\z/',
                            $this->view->tag('p', array('class' => 'show',
                                                        'class' => 'elsewhere')));
    }

    public function testTagOptionsRejectsNullOption()
    {
        $this->assertEquals('<p>',
                            $this->view->tag('p', array('ignored' => null)));
    }

    public function testTagOptionsAcceptsBlankOption()
    {
        $this->assertEquals('<p included="">',
                            $this->view->tag('p', array('included' => '')));
    }

    public function testTagOptionsConvertsBooleanOption()
    {
        $this->assertEquals('<p disabled multiple readonly>',
                            $this->view->tag('p', array('disabled' => true,
                                                        'multiple' => true,
                                                        'readonly' => true)));
    }

    public function testContentTag()
    {
        $this->assertEquals('<a href="create">Create</a>',
                            $this->view->contentTag('a', 'Create', array('href' => 'create')));
    }

    public function testCdataSection()
    {
        $this->assertEquals('<![CDATA[<hello world>]]>', $this->view->cdataSection('<hello world>'));
    }

    public function testEscapeOnce()
    {
        $this->assertEquals('1 &lt; 2 &amp; 3', $this->view->escapeOnce('1 < 2 &amp; 3'));
    }

    public function testDoubleEscapingAttributes()
    {
        $attributes = array('1&amp;2', '1 &lt; 2', '&#8220;test&#8220;');
        foreach ($attributes as $escaped) {
            $this->assertEquals("<a href=\"$escaped\">",
                                $this->view->tag('a', array('href' => $escaped)));
        }
    }

    public function testSkipInvalidEscapedAttributes()
    {
        $attributes = array('&1;', '&#1dfa3;', '& #123;');
        foreach ($attributes as $escaped) {
            $this->assertEquals('<a href="' . str_replace('&', '&amp;', $escaped) . '">',
                                $this->view->tag('a', array('href' => $escaped)));
        }
    }

}
