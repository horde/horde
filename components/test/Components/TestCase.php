<?php
/**
 * Test base.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Test base.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getComponentFactory(
        $arguments = array(), $options = array()
    )
    {
        $dependencies = new Components_Dependencies_Injector();
        $config = new Components_Stub_Config($arguments, $options);
        $dependencies->initConfig($config);
        return $dependencies->getComponentFactory();
    }

    protected function getComponent(
        $directory, $arguments = array(), $options = array()
    )
    {
        $dependencies = new Components_Dependencies_Injector();
        $config = new Components_Stub_Config($arguments, $options);
        $dependencies->initConfig($config);
        $factory = $dependencies->getComponentFactory();
        return new Components_Component_Source(
            $directory, $config, $factory
        );
    }

    protected function getReleaseTask($name, $package)
    {
        $dependencies = new Components_Dependencies_Injector();
        $this->output = new Components_Stub_Output();
        $dependencies->setInstance('Components_Output', $this->output);
        return $dependencies->getReleaseTasks()->getTask($name, $package);
    }

    protected function getReleaseTasks()
    {
        $dependencies = new Components_Dependencies_Injector();
        $this->output = new Components_Stub_Output();
        $dependencies->setInstance('Components_Output', $this->output);
        return $dependencies->getReleaseTasks();
    }

    protected function getTemporaryDirectory()
    {
        return Horde_Util::createTempDir();
    }

    protected function getHelp()
    {
        $_SERVER['argv'] = array('horde-components', '--help');
        return $this->_callStrictComponents();
    }

    protected function getActionHelp($action)
    {
        $_SERVER['argv'] = array('horde-components', 'help', $action);
        return $this->_callStrictComponents();
    }

    protected function _callStrictComponents(array $parameters = array())
    {
        return $this->_callComponents($parameters, array($this, '_callStrict'));
    }

    protected function _callUnstrictComponents(array $parameters = array())
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

    protected function fileRegexpPresent($regex, $dir)
    {
        $files = array();
        $found = false;
        foreach (new DirectoryIterator($dir) as $file) {
            if (preg_match($regex, $file->getBasename('.tgz'), $matches)) {
                $found = true;
            }
            $files[] = $file->getPath();
        }
        $this->assertTrue(
            $found,
            sprintf("File \"%s\" not found in \n\n%s\n", $regex, join("\n", $files))
        );
    }

    protected function setPearGlobals()
    {
        $GLOBALS['_PEAR_ERRORSTACK_DEFAULT_CALLBACK'] = array(
            '*' => false,
        );
        $GLOBALS['_PEAR_ERRORSTACK_DEFAULT_LOGGER'] = false;
        $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = array();
    }

    protected function changeDirectory($path)
    {
        $this->cwd = getcwd();
        chdir($path);
    }

    protected function lessStrict()
    {
        $this->old_errorreporting = error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
    }

    public function tearDown()
    {
        if (!empty($this->cwd)) {
            chdir($this->cwd);
        }
        if (!empty($this->old_errorreporting)) {
            error_reporting($this->old_errorreporting);
        }
    }
}