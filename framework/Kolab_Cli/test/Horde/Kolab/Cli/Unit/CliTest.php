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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the CLI interface.
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
}
