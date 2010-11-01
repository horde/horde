<?php

require_once dirname(__FILE__) . '/ConflictTestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_ConflictTest extends Horde_Argv_ConflictTestCase
{
    public function assertConflictError($func)
    {
        try {
            call_user_func($func, '-v', '--version', array(
                'action'   => 'callback',
                'callback' => array($this, 'showVersion'),
                'help'     => 'show version'));
            $this->fail();
        } catch (Horde_Argv_OptionConflictException $e) {
            $this->assertEquals("option -v/--version: conflicting option string(s): -v",
                                $e->getMessage());
            $this->assertEquals('-v/--version', $e->optionId);
        }
    }

    public function testConflictError()
    {
        $this->assertConflictError(array($this->parser, 'addOption'));
    }

    public function testConflictErrorGroup()
    {
        $group = new Horde_Argv_OptionGroup($this->parser, 'Group 1');
        $this->assertConflictError(array($group, 'addOption'));
    }

    public function testNoSuchConflictHandler()
    {
        $this->assertRaises(array($this->parser, 'setConflictHandler'), array('foo'), 'InvalidArgumentException', "invalid conflictHandler 'foo'");
    }

}
