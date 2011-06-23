<?php
/**
 * Test the file based configuration handler.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the file based configuration handler.
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
class Components_Unit_Components_Config_FileTest
extends Components_TestCase
{
    public function testGetOption()
    {
        $config = $this->_getFileConfig();
        $options = $config->getOptions();
        $this->assertTrue($options['test']);
    }

    public function testArgumentsEmpty()
    {
        $this->assertEquals(
            array(),
            $this->_getFileConfig()->getArguments()
        );
    }

    private function _getFileConfig()
    {
        $path = Components_Constants::getConfigFile();
        return new Components_Config_File(
            $path . '.dist'
        );
    }
}