<?php
/**
 * Base for story based package testing.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Qc
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Qc
 */

/**
 * Base for story based package testing.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Qc
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc_StoryTestCase
extends PHPUnit_Extensions_Story_TestCase
{
    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the default QC package setup':
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'calling the package with the help option':
            $_SERVER['argv'] = array('hqc', '--help', '--packagexml');
            ob_start();
            $parameters = array();
            $parameters['config']['cli']['parser']['class'] = 'Horde_Qc_Stub_Parser';
            Horde_Qc::main($parameters);
            $world['output'] = ob_get_contents();
            ob_end_clean();
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the help will be displayed':
            $this->assertRegExp(
                '/-h,[ ]*--help[ ]*show this help message and exit/',
                $world['output']
            );
            break;
        case 'the help will contain the "p" option.':
            $this->assertRegExp(
                '/-p,\s*--packagexml/m',
                $world['output']
            );
            break;
        case 'the help will contain the "u" option.':
            $this->assertRegExp(
                '/-u,\s*--updatexml/',
                $world['output']
            );
            break;
        default:
            return $this->notImplemented($action);
        }
    }

}