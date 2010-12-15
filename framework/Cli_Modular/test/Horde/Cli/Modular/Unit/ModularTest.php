<?php
/**
 * Test the module wrapper.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the module wrapper.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Cli_Modular_Unit_ModularTest
extends Horde_Cli_Modular_TestCase
{
    public function testParser()
    {
        $modular = new Horde_Cli_Modular();
        $this->assertInstanceOf('Horde_Argv_Parser', $modular->createParser());
    }

    public function testCustomParser()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'cli' => array(
                    'parser' => array(
                        'class' => 'Horde_Test_Stub_Parser'
                    )
                )
            )
        );
        $this->assertInstanceOf('Horde_Test_Stub_Parser', $modular->createParser());
    }
}
