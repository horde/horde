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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the options of the CLI interface.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Unit_Cli_OptionsTest
extends Horde_Kolab_Cli_TestCase
{
    public function testOptionHelp()
    {
        setlocale(LC_MESSAGES, 'C');
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

    public function testOptionLog()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/-l[ ]*LOG,[ ]*--log=LOG/',
            $this->runCli()
        );
    }
}
