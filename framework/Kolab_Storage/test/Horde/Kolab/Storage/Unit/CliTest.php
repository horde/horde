<?php
/**
 * Test the CLI interface.
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
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_CliTest
extends PHPUnit_Framework_TestCase
{
    public function testCli()
    {
        $_SERVER['argv'] = array(
            'kolab-storage'
        );
        ob_start();
        Horde_Kolab_Storage_Cli::main(
            array(
                'output' => new Horde_Test_Stub_Cli(),
                'parser' => array('class' => 'Horde_Test_Stub_Parser')
            )
        );
        $output = ob_get_contents();
        ob_end_clean();
    }
}
