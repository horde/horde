<?php
/**
 * Test the options of the CLI interface.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the options of the CLI interface.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Cli_OptionsTest
extends Horde_Kolab_Storage_TestCase
{
    public function testOptionHelp()
    {
        $_SERVER['argv'] = array(
            'kolab-storage'
        );
        $this->assertRegExp(
            '/-h,[ ]*--help[ ]*show this help message and exit/',
            $this->runCli()
        );
    }

    public function testOptionDriver()
    {
        $_SERVER['argv'] = array(
            'kolab-storage'
        );
        $this->assertRegExp(
            '/-d[ ]*DRIVER,[ ]*--driver=DRIVER/',
            $this->runCli()
        );
    }
}
