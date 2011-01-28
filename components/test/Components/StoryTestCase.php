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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        case 'calling the package with the updatexml option and a path without package.xml':
            $temp = $this->_getTemporaryDirectory();
            mkdir($temp . DIRECTORY_SEPARATOR . 'test');
            file_put_contents(
                $temp . DIRECTORY_SEPARATOR . 'test'  . DIRECTORY_SEPARATOR . 'test.php',
                '<?php'
            );
            $_SERVER['argv'] = array(
                'horde-components',
                '--updatexml',
                $temp
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
        case 'calling the package with the packagexml option and a component with empty changelog':
            $_SERVER['argv'] = array(
                'horde-components',
                '--pearrc=' . $this->_getTemporaryDirectory() . DIRECTORY_SEPARATOR . '.pearrc',
                '--packagexml',
                dirname(__FILE__) . '/fixture/changelog'
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
        case 'calling the package with the install option, the pretend option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--channelxmlpath=' . dirname(__FILE__) . '/fixture/channels',
                '--sourcepath=' . dirname(__FILE__) . '/fixture/packages',
                '--pretend',
                '--install=' . $this->_getTemporaryDirectory() . DIRECTORY_SEPARATOR . '.pearrc',
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the install option, a path to a Horde framework component, and the following include/exclude options':
            $_SERVER['argv'] = array(
                'horde-components',
                '--channelxmlpath=' . dirname(__FILE__) . '/fixture/channels',
                '--sourcepath=' . dirname(__FILE__) . '/fixture/packages',
                '--pretend',
                '--include=' . $arguments[0],
                '--exclude=' . $arguments[1],
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
        case 'calling the package with the list dependencies option, the nocolor option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--nocolor',
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
        case 'calling the package with the devpackage option, the archive directory option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--verbose',
                '--devpackage',
                '--archivedir=' . $this->_getTemporaryDirectory(),
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the devpackage option, the archive directory option and a path to an invalid Horde framework component':
            $this->_setPearGlobals();
            $cwd = getcwd();
            $_SERVER['argv'] = array(
                'horde-components',
                '--verbose',
                '--devpackage',
                '--archivedir=' . $this->_getTemporaryDirectory(),
                dirname(__FILE__) . '/fixture/simple'
            );
            try {
                $world['output'] = $this->_callUnstrictComponents();
            } catch (Components_Exception_Pear $e) {
                ob_end_clean();
                $world['output'] = (string) $e;
            }
            chdir($cwd);
            break;
        case 'calling the package with the distribute option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--templatedir=' . dirname(__FILE__) . '/fixture/templates/distribute/openpkg',
                '--distribute=' . $this->_getTemporaryDirectory(),
                dirname(__FILE__) . '/fixture/framework/Install'
            );
            $world['output'] = $this->_callUnstrictComponents();
            break;
        case 'calling the package with the document option and a path to a Horde framework component':
            $_SERVER['argv'] = array(
                'horde-components',
                '--templatedir=' . dirname(__FILE__) . '/fixture/templates/html',
                '--document=' . $this->_getTemporaryDirectory(),
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
        case 'the new package.xml of the Horde component will not contain the file':
            $this->assertNotRegExp(
                '#' . $arguments[0] . '#',
                $world['output']
            );
            break;
        case 'the new package.xml of the Horde component will contain the file':
            $this->assertRegExp(
                '#' . $arguments[0] . '#',
                $world['output']
            );
            break;
        case 'a new package.xml will be created.':
            $this->assertTrue(
                file_exists($this->_temp_dir . DIRECTORY_SEPARATOR . 'package.xml')
            );
            break;
        case 'the new package.xml of the Horde component will have a changelog entry':
            $this->assertRegExp(
                '#</changelog>#',
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
        case 'the dummy PEAR package will be listed':
            $this->assertContains(
                'Would install external package pear.php.net/PEAR',
                $world['output']
            );
            break;
        case 'the non-Horde dependencies of the component would be installed':
            $this->assertContains(
                'Would install external package pear.php.net/Console_Getopt',
                $world['output']
            );
            break;
        case 'the PECL will package will be listed':
            $this->assertContains(
                'Would install external package pecl.php.net/PECL',
                $world['output']
            );
            break;
        case 'the PECL will package will not be listed':
            $this->assertNotContains(
                'Would install external package pecl.php.net/PECL',
                $world['output']
            );
            break;
        case 'the Console_Getopt package will be listed':
            $this->assertContains(
                'Would install external package pear.php.net/Console_Getopt',
                $world['output']
            );
            break;
        case 'the Console_Getopt package will not be listed':
            $this->assertNotContains(
                'Would install external package pear.php.net/Console_Getopt',
                $world['output']
            );
            break;
        case 'the Horde dependencies of the component would be installed':
            $trimmed = strtr($world['output'], array(' ' => '', "\n" => ''));
            $this->assertRegExp(
                '#Wouldinstallpackage.*Dependency/package.xml#',
                $trimmed
            );
            break;
        case 'the old-style Horde dependencies of the component would be installed':
            $trimmed = strtr($world['output'], array(' ' => '', "\n" => ''));
            $this->assertRegExp(
                '#Wouldinstallpackage.*Old/package.xml#',
                $trimmed
            );
            break;
        case 'the Optional package will be listed':
            $trimmed = strtr($world['output'], array(' ' => '', "\n" => ''));
            $this->assertRegExp(
                '#Wouldinstallpackage.*Optional/package.xml#',
                $trimmed
            );
            break;
        case 'the Optional package will not be listed':
            $trimmed = strtr($world['output'], array(' ' => '', "\n" => ''));
            $this->assertNotRegExp(
                '#Wouldinstallpackage.*Optional/package.xml#',
                $trimmed
            );
            break;
        case 'the component will be listed':
            $trimmed = strtr($world['output'], array(' ' => '', "\n" => ''));
            $this->assertRegExp(
                '#Wouldinstallpackage.*Install/package.xml#',
                $trimmed
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
        case 'the non-Horde dependencies of the component will not be listed':
            $this->assertNotContains(
                'Console_Getopt',
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
        case 'a package snapshot will be generated at the indicated archive directory':
            $found = false;
            foreach (new DirectoryIterator($this->_temp_dir) as $file) {
                if (preg_match('/Install-[0-9]+(\.[0-9]+)+([a-z0-9]+)?/', $file->getBasename('.tgz'), $matches)) {
                    $found = true;
                }
            }
            $this->assertTrue($found);
            break;
        case 'a package definition will be generated at the indicated location':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'Install.spec'
                )
            );
            break;
        case 'the package documentation will be generated at the indicated location':
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'index.html'
                )
            );
            $this->assertTrue(
                file_exists(
                    $this->_temp_dir . DIRECTORY_SEPARATOR
                    . 'install.html'
                )
            );
            break;
        case 'the output should indicate an invalid package.xml':
            $this->assertContains(
                'PEAR_Packagefile_v2::toTgz: invalid package.xml',
                $world['output']
            );
            break;
        case 'indicate the specific problem of the package.xml':
            $this->assertContains(
                'Old.php" in package.xml does not exist',
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
        $parameters['parser']['class'] = 'Horde_Test_Stub_Parser';
        $parameters['dependencies'] = new Components_Dependencies_Injector();
        $parameters['dependencies']->setInstance(
            'Horde_Cli',
            new Horde_Test_Stub_Cli()
        );
        call_user_func_array($callback, array($parameters));
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    private function _callUnstrict(array $parameters)
    {
        $old_errorreporting = error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        $this->_callStrict($parameters);
        error_reporting($old_errorreporting);
    }

    private function _callStrict(array $parameters)
    {
        Components::main($parameters);
    }

    private function _setPearGlobals()
    {
        $GLOBALS['_PEAR_ERRORSTACK_DEFAULT_CALLBACK'] = array(
            '*' => false,
        );
        $GLOBALS['_PEAR_ERRORSTACK_DEFAULT_LOGGER'] = false;
        $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = array();
    }
}