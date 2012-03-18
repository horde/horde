<?php
/**
 * Test the modules of the CLI interface.
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
 * Test the modules of the CLI interface.
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
class Horde_Kolab_Cli_Unit_Cli_ModuleTest
extends Horde_Kolab_Cli_TestCase
{
    public function testFolderModule()
    {
        $_SERVER['argv'] = array(
            'klb'
        );
        $this->assertRegExp(
            '/list - Handle folder lists/',
            $this->runCli()
        );
    }
}
