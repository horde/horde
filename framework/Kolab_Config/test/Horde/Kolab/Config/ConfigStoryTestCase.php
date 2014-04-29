<?php
/**
 * Base for story based package testing.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */

/**
 * Base for story based package testing.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */
class Horde_Kolab_Config_ConfigStoryTestCase
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
        case 'that no Kolab server configuration file can be found':
            $world['config'] = new Horde_Kolab_Config(
                __DIR__ . '/fixture/empty'
            );
            break;
        case 'that a global configuration file was specified as a combination of a directory path and a file name':
            $world['config'] = new Horde_Kolab_Config(
                __DIR__ . '/fixture/global',
                'globals.conf'
            );
            break;
        case 'that the location of the configuration files were specified with a directory path':
            $world['config'] = new Horde_Kolab_Config(
                __DIR__ . '/fixture/local'
            );
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
        case 'reading the configuration':
            try {
                $world['config']->read();
            } catch (Horde_Kolab_Config_Exception $e) {
                $world['result'] = $e;
            }
            break;
        case 'reading the parameter':
            try {
                $world['result'] = $world['config'][$arguments[0]];
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
        case 'the Config Object will throw an exception of type':
            $this->assertInstanceOf(
                $arguments[0], $world['result']
            );
            break;
        case 'the exception has the message':
            $this->assertEquals(
                $arguments[0], $world['result']->getMessage()
            );
            break;
        case 'the result will be':
            if ($world['result'] instanceOf Exception) {
                $this->assertEquals(
                    '', $world['result']->getMessage()
                );
            } else {
                $this->assertEquals($arguments[0], $world['result']);
            }
            break;
        default:
            return $this->notImplemented($action);
        }
    }

}