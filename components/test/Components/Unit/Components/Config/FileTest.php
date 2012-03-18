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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the file based configuration handler.
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
class Components_Unit_Components_Config_FileTest
extends Components_TestCase
{
    public function testGetOption()
    {
        $config = $this->_getFileConfig();
        $options = $config->getOptions();
        $this->assertEquals('pear.horde.org', $options['releaseserver']);
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