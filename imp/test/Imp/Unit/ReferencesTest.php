<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @copyright  2012-2014 Horde LLC
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test References parsing code.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2012-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_ReferencesTest extends PHPUnit_Framework_TestCase
{
    public function testBasicParsing()
    {
        $refs = '<foo@example.com> <foo2@example.com> <foo3@example.com>';

        $ob = new IMP_Compose_References();
        $ob->parse($refs);

        $this->assertEquals(
            3,
            count($ob->references)
        );
    }

    public function testParsingWithoutSpaces()
    {
        $refs = '<foo@example.com><foo2@example.com><foo3@example.com>';

        $ob = new IMP_Compose_References();
        $ob->parse($refs);

        $this->assertEquals(
            3,
            count($ob->references)
        );
    }

    public function testParsingWithCommas()
    {
        $refs = '<foo@example.com>, <foo2@example.com>,<foo3@example.com>';

        $ob = new IMP_Compose_References();
        $ob->parse($refs);

        $this->assertEquals(
            3,
            count($ob->references)
        );
    }

    public function testComplexParse()
    {
        $refs = '<foo@example.com>, <foo2@example.com>,<foo3@example.com> ' .
            '<foo4@example.com>  <foo5@example.com>';

        $ob = new IMP_Compose_References();
        $ob->parse($refs);

        $this->assertEquals(
            5,
            count($ob->references)
        );
    }

    public function testBug11953()
    {
        $refs = '<foo@example@example.com>';

        $ob = new IMP_Compose_References();
        $ob->parse($refs);

        $this->assertEquals(
            1,
            count($ob->references)
        );

        $this->assertEquals(
            $refs,
            reset($ob->references)
        );
    }

}
