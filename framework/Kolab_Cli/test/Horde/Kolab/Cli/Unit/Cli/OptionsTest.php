<?php
/**
 * Test the options of the CLI interface.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
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
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Unit_Cli_OptionsTest
extends Horde_Kolab_Cli_TestCase
{
    public function testOptionHelp()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-h,[ ]*--help[ ]*show this help message and exit/',
            $this->runCli()
        );
    }

    public function testOptionDriver()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-d[ ]*DRIVER,[ ]*--driver=DRIVER/',
            $this->runCli()
        );
    }

    public function testOptionUser()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-u[ ]*USERNAME,[ ]*--username=USERNAME/',
            $this->runCli()
        );
    }

    public function testOptionPass()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-p[ ]*PASSWORD,[ ]*--password=PASSWORD/',
            $this->runCli()
        );
    }

    public function testOptionHost()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-H[ ]*HOST,[ ]*--host=HOST/',
            $this->runCli()
        );
    }

    public function testOptionTimed()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-t,[ ]*--timed/',
            $this->runCli()
        );
    }
}
