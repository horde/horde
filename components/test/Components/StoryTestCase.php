<?php
/**
 * Base for story based package testing.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
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
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_StoryTestCase
extends PHPUnit_Extensions_Story_TestCase
{
    public function tearDown()
    {
        if (!empty($this->_temp_dir)) {
            $this->_rrmdir($this->_temp_dir);
        }
    }

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
        case 'the default Components setup':
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
            $_SERVER['argv'] = array(
                'horde-components',
                '--help',
                dirname(__FILE__) . '/fixture/empty'
            );
            ob_start();
            $parameters = array();
            $parameters['cli']['parser']['class'] = 'Components_Stub_Parser';
            Components::main($parameters);
            $world['output'] = ob_get_contents();
            ob_end_clean();
            break;
        case 'calling the package with the packagexml option and a Horde element':
            $_SERVER['argv'] = array(
                'horde-components',
                '--packagexml',
                dirname(__FILE__) . '/fixture/simple'
            );
            ob_start();
            $parameters = array();
            $parameters['cli']['parser']['class'] = 'Components_Stub_Parser';
            $old_errorreporting = error_reporting(E_ALL & ~E_STRICT);
            Components::main($parameters);
            error_reporting($old_errorreporting);
            $world['output'] = ob_get_contents();
            ob_end_clean();
            break;
        case 'calling the package with the install option and a Horde element':
            $_SERVER['argv'] = array(
                'horde-components',
                '--install=' . $this->_getTemporaryDirectory(),
                dirname(__FILE__) . '/../../'
            );
            ob_start();
            $parameters = array();
            $parameters['cli']['parser']['class'] = 'Components_Stub_Parser';
            $old_errorreporting = error_reporting(E_ALL & ~E_STRICT);
            Components::main($parameters);
            error_reporting($old_errorreporting);
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
        case 'the help will contain the "d" option.':
            $this->assertRegExp(
                '/-d,\s*--devpackage/',
                $world['output']
            );
            break;
        case 'the help will contain the "i" option.':
            $this->assertRegExp(
                '/-i\s*INSTALL,\s*--install=INSTALL/',
                $world['output']
            );
            break;
        case 'the new package.xml of the Horde element will be printed.':
            $this->assertRegExp(
                '/<file name="New.php" role="php" \/>/',
                $world['output']
            );
            break;
        case 'a new PEAR configuration file will be installed':
            $this->assertTrue(
                file_exists($this->_temp_dir . DIRECTORY_SEPARATOR . '.pearrc')
            );
            break;
        case 'the PEAR package will be installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'PEAR.php'
                )
            );
            break;
        case 'the non-Horde dependencies of the Horde element will get installed from the network.':
            var_dump($world['output']);
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'PEAR' . DIRECTORY_SEPARATOR
                    . 'PackageFileManager2.php'
                )
            );
            break;
        case 'the Horde dependencies of the Horde element will get installed from the current tree.':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Horde' . DIRECTORY_SEPARATOR
                    . 'Autoloader.php'
                )
            );
            break;
        case 'the Horde element will be installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Horde' . DIRECTORY_SEPARATOR
                    . 'Components.php'
                )
            );
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    private function _getTemporaryDirectory()
    {
        $this->_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'Components_' . mt_rand();
        mkdir($this->_temp_dir);
        return $this->_temp_dir;
    }

    private function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . DIRECTORY_SEPARATOR . $object) == 'dir') {
                        $this->_rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    } 
}