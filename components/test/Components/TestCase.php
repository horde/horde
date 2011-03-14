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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Test base.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Components_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getTemporaryDirectory()
    {
        return Horde_Util::createTempDir();
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
}