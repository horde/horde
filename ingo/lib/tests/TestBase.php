<?php
/**
 * Common library for Ingo test cases
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 * @subpackage UnitTests
 */
class Ingo_TestBase extends PHPUnit_Framework_TestCase {

    function _enableRule($rule)
    {
        $filters = $GLOBALS['injector']->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_FILTERS);
        foreach ($filters->getFilterList() as $k => $v) {
            if ($v['action'] == $rule) {
                $v['disable'] = false;
                $filters->updateRule($v, $k);
                $this->store($filters);
            }
        }
    }

    function assertScript($expect)
    {
        $result = $GLOBALS['ingo_script']->generate();
        if (!is_string($result)) {
            $this->fail("result not a script", 1);
            return;
        }

        /* Remove comments and crunch whitespace so we can have a functional
         * comparison. */
        $new = array();
        foreach (explode("\n", $result) as $line) {
            if (preg_match('/^\s*$/', $line)) {
                continue;
            }
            if (preg_match('/^\s*#.*$/', $line)) {
                continue;
            }
            $new[] = trim($line);
        }

        $new_script = join("\n", $new);
        $this->assertEquals($expect, $new_script);
    }

}

class Ingo_Test_Notification {

    function push()
    {
    }

}
