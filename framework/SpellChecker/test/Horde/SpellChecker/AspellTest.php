<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  SpellChecker
 */

/**
 * Tests for IMAP mailbox sorting.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  SpellChecker
 */
class Horde_SpellChecker_AspellTest extends PHPUnit_Framework_TestCase
{
    protected $aspell;

    public function setUp()
    {
        $aspell = trim(`which aspell`);
        if (!is_executable($aspell)) {
            $aspell = trim(`which ispell`);
        }

        if (!is_executable($aspell)) {
            $this->markTestSkipped('No aspell/ispell binary found.');
        }

        $this->aspell = Horde_SpellChecker::factory('Aspell', array(
            'path' => $aspell
        ));
    }

    public function testAspell()
    {
        $res = $this->aspell->spellCheck('some tet [mispeled] ?');

        $this->assertNotEmpty($res);
        $this->assertNotEmpty($res['bad']);
        $this->assertEquals(
            $res['bad'],
            array('tet', 'mispeled')
        );
        $this->assertNotEmpty($res['suggestions']);
        $this->assertNotEmpty($res['suggestions'][0]);
        $this->assertNotEmpty($res['suggestions'][1]);
    }

}
