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
            $world['output'] = $this->_callStrictComponents();
            break;
        case 'calling the package with the packagexml option and a Horde component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--packagexml',
                dirname(__FILE__) . '/fixture/simple'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the pearrc, the packagexml option, and a Horde component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--pearrc=' . $this->_getTemporaryDirectory() . DIRECTORY_SEPARATOR . '.pearrc',
                '--packagexml',
                dirname(__FILE__) . '/fixture/simple'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the packagexml option and the path':
            $_SERVER['argv'] = array(
                'horde-components',
                '--packagexml',
                $arguments[0]
            );
            $world['output'] = $this->_callStrictComponents();
            break;
        case 'calling the package with the cisetup option and paths':
            $_SERVER['argv'] = array(
                'horde-components',
                '--cisetup=' . $arguments[0],
                $arguments[1]
            );
            $world['output'] = $this->_callStrictComponents();
            break;
        case 'calling the package with the cisetup, toolsdir options and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--toolsdir=/DUMMY_TOOLS',
                '--cisetup=' . $arguments[0],
                $arguments[1]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the cisetup, toolsdir, pearrc options and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--cisetup=' . $tmp,
                '--toolsdir=/DUMMY_TOOLS',
                '--pearrc=' . $tmp . DIRECTORY_SEPARATOR . '.pearrc',
                $arguments[0]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the ciprebuild option and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--ciprebuild=' . $tmp,
                $arguments[0]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the ciprebuild, toolsdir option and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--ciprebuild=' . $tmp,
                '--toolsdir=/DUMMY_TOOLS',
                $arguments[0]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the cisetup, toolsdir, pearrc, template options and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--cisetup=' . $tmp,
                '--toolsdir=/DUMMY_TOOLS',
                '--pearrc=' . $tmp . DIRECTORY_SEPARATOR . '.pearrc',
                '--templatedir=' . dirname(__FILE__) . '/fixture/templates',
                $arguments[0]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the ciprebuild, toolsdir, template options and path':
            $tmp = $this->_getTemporaryDirectory();
            $_SERVER['argv'] = array(
                'horde-components',
                '--ciprebuild=' . $tmp,
                '--toolsdir=/DUMMY_TOOLS',
                '--templatedir=' . dirname(__FILE__) . '/fixture/templates',
                $arguments[0]
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the install option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--channelxmlpath=' . dirname(__FILE__) . '/fixture/channels',
                '--sourcepath=' . dirname(__FILE__) . '/fixture/packages',
                '--install=' . $this->_getTemporaryDirectory() . DIRECTORY_SEPARATOR . '.pearrc',
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the list dependencies option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--list-deps',
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the verbose list dependencies option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--verbose',
                '--list-deps',
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the quiet list dependencies option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--quiet',
                '--list-deps',
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
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
        case 'the help will contain the option':
            $this->assertRegExp(
                '/' . $arguments[0] . '/',
                $world['output']
            );
            break;
        case 'the new package.xml of the Horde element will be printed.':
            $this->assertRegExp(
                '/<file name="New.php" role="php" \/>/',
                $world['output']
            );
            break;
        case 'the new package.xml of the Horde component will retain all "replace" tasks.':
            $this->assertRegExp(
                '#<tasks:replace from="@data_dir@" to="data_dir" type="pear-config" />#',
                $world['output']
            );
            break;
        case 'the new package.xml will install java script files in a default location':
            $this->assertRegExp(
                '#<install as="js/test.js" name="js/test.js" />#',
                $world['output']
            );
            break;
        case 'the new package.xml will install migration files in a default location':
            $this->assertRegExp(
                '#<install as="migration/test.sql" name="migration/test.sql" />#',
                $world['output']
            );
            break;
        case 'the new package.xml will install script files in a default location':
            $this->assertRegExp(
                '#<install as="other_script" name="script/other_script" />#',
                $world['output']
            );
            $this->assertRegExp(
                '#<install as="shell_script.sh" name="script/shell_script.sh" />#',
                $world['output']
            );
            $this->assertRegExp(
                '#<install as="script" name="script/script.php" />#',
                $world['output']
            );
            break;
        case 'a new PEAR configuration file will be installed':
            $this->assertTrue(
                file_exists($this->_temp_dir . DIRECTORY_SEPARATOR . '.pearrc')
            );
            break;
        case 'the dummy PEAR package will be installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'PEAR.php'
                )
            );
            break;
        case 'the non-Horde dependencies of the component will get installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Console.php'
                )
            );
            break;
        case 'the Horde dependencies of the component will get installed from the current tree':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Dependency.php'
                )
            );
            break;
        case 'the Components library will be installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Components.php'
                )
            );
            break;
        case 'the component will be installed':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'pear' . DIRECTORY_SEPARATOR 
                    . 'php' . DIRECTORY_SEPARATOR
                    . 'Install.php'
                )
            );
            break;
        case 'the CI configuration will be installed.':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'config.xml'
                )
            );
            break;
        case 'the installation requires no network access.':
            $this->assertNotContains(
                'network',
                $world['output']
            );
            break;
        case 'the CI build script will be installed.':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'build.xml'
                )
            );
            break;
        case 'the CI configuration will be installed according to the specified template.':
            $this->assertEquals(
                "CONFIG.XML\n",
                file_get_contents(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'config.xml'
                )
            );
            break;
        case 'the CI build script will be installed according to the specified template.':
            $this->assertEquals(
                "BUILD.XML\n",
                file_get_contents(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'build.xml'
                )
            );
            break;
        case 'the call will fail with':
            $this->assertContains(
                $arguments[0],
                $world['output']
            );
            break;
        case 'the non-Horde dependencies of the component will be listed':
            $this->assertContains(
                'Console_Getopt',
                $world['output']
            );
            break;
        case 'the Horde dependencies of the component will be listed':
            $this->assertContains(
                'Dependency',
                $world['output']
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

    private function _callStrictComponents(array $parameters = array())
    {
        return $this->_callComponents($parameters, array($this, '_callStrict'));
    }

    private function _callUnstrictComponents(array $parameters = array())
    {
        return $this->_callComponents($parameters, array($this, '_callUnstrict'));
    }

    private function _callComponents(array $parameters, $callback)
    {
        ob_start();
        $parameters['cli']['parser']['class'] = 'Components_Stub_Parser';
        $parameters['dependencies'] = new Components_Dependencies_Injector();
        $parameters['dependencies']->setInstance(
            'Horde_Cli',
            new Components_Stub_Cli()
        );
        call_user_func_array($callback, array($parameters));
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    private function _callUnstrict(array $parameters)
    {
        $old_errorreporting = error_reporting(E_ALL & ~E_STRICT);
        $this->_callStrict($parameters);
        error_reporting($old_errorreporting);
    }

    private function _callStrict(array $parameters)
    {
        Components::main($parameters);
    }
}