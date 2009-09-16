<?php
/**
 * All tests for the Provider:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the Horde_Provider class.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */
class Horde_Provider_ProviderScenario extends PHPUnit_Extensions_Story_TestCase
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
        case 'a provider':
            $world['provider'] = new Horde_Provider_Base();
            break;
        case 'a registered element':
            $world['provider']->{$arguments[0]} = $arguments[1];
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
        case 'retrieving the element':
            try {
                $world['result'] = $world['provider']->{$arguments[0]};
            } catch (Exception $e) {
                $world['result'] = $e;
            }
            break;
        case 'deleting the element':
            try {
                unset($world['provider']->{$arguments[0]});
            } catch (Exception $e) {
                $world['result'] = $e;
            }
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
        case 'the result is':
            $this->assertEquals($arguments[0], $world['result']);
            break;
        case 'the result is an error':
            $this->assertTrue($world['result'] instanceOf Exception);
            break;
        case 'the result is an error with the message':
            $this->assertTrue($world['result'] instanceOf Exception);
            $this->assertEquals($arguments[0], $world['result']->getMessage());
            break;
        case 'the element exists':
            $this->assertTrue(isset($world['provider']->{$arguments[0]}));
            break;
        default:
            return $this->notImplemented($action);
        }
    }
}
