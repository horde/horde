<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Css_Parser
 * @subpackage UnitTests
 */

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @ignore
 * @package    Css_Parser
 * @subpackage UnitTests
 */
class Horde_Css_Parser_ParserTest extends PHPUnit_Framework_TestCase
{
    public function testDoubleAsteriskAtBeginningOfComment()
    {
        $a = '/** Foo */#bar{width:1px;}';

        $css = new Horde_Css_Parser($a);

        $this->assertEquals(
            '#bar{width:1px}',
            $css->compress()
        );
    }

    /**
     * @small
     */
    public function testEmptyDocument()
    {
        $css = new Horde_Css_Parser('');
        $this->assertEquals('', $css->compress());
    }
}
