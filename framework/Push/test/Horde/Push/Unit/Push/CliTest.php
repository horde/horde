<?php
/**
 * Test the CLI.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the CLI.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Unit_Push_CliTest
extends Horde_Push_TestCase
{
    public function tearDown()
    {
        unset($_SERVER);
    }

    public function testEmpty()
    {
        ob_start();
        $_SERVER['argv'] = array(
            'test',
            'yaml://' . __DIR__ . '/../../fixtures/push.yaml'
        );
        Horde_Push_Cli::main(array('no_exit' => true));
        $output = ob_get_clean();
        $this->assertContains('Pushed "YAML".', $output);
    }
}
