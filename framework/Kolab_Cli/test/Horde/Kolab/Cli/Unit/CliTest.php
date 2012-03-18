<?php
/**
 * Test the CLI interface.
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
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the CLI interface.
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
class Horde_Kolab_Cli_Unit_CliTest
extends Horde_Kolab_Cli_TestCase
{
    public function testCli()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->runCli();
    }

    public function testUsage()
    {
        setlocale(LC_MESSAGES, 'C');
        $_SERVER['argv'] = array(
            'klb',
            '--driver=mock',
            '--user=test',
            'DOESNOTEXISTS'
        );
        $this->assertContains('Usage:', $this->runCli());
    }

    public function testFolderList()
    {
        $_SERVER['argv'] = array(
            'klb',
            '--driver=mock',
            '--user=test',
            'folder'
        );
        $this->assertContains('INBOX', $this->runCli());
    }

    public function testTimeInfo()
    {
        $_SERVER['argv'] = array(
            'klb',
            '--driver=mock',
            '--timed',
            '--user=test',
            'folder'
        );
        $this->assertContains('[  INFO  ]', $this->runCli());
    }

    public function testTimed()
    {
        $_SERVER['argv'] = array(
            'klb',
            '--driver=mock',
            '--timed',
            '--user=test',
            'folder'
        );
        $this->assertRegExp('/[0-9]+ ms/', $this->runCli());
    }

    public function testTimeMissing()
    {
        $_SERVER['argv'] = array(
            'klb',
            '--driver=mock',
            '--user=test',
            'folder'
        );
        $this->assertNotContains('[  INFO  ]', $this->runCli());
    }
}
