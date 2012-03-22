<?php
/**
 * Test the tasklists factory.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the tasklists factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Nag_Unit_Factory_TasklistsTest extends Nag_TestCase
{
    public function setUp()
    {
        $setup = self::createKolabSetup();
        $error = $setup->getError();
        if (!empty($error)) {
            $this->markTestSkipped($error);
        }
    }

    /**
     * @expectedException Nag_Exception
     */
    public function testInvalidDefinition()
    {
        $GLOBALS['conf']['tasklists']['driver'] = 'Invalid';
        $factory = new Nag_Factory_Tasklists($this->getInjector());
        $factory->create();
    }

    public function testMissingDefinition()
    {
        unset($GLOBALS['conf']['tasklists']['driver']);
        $factory = new Nag_Factory_Tasklists($this->getInjector());
        $this->assertInstanceOf(
            'Nag_Tasklists_Default',
            $factory->create()
        );
    }

    public function testDefaultDefinition()
    {
        $GLOBALS['conf']['tasklists']['driver'] = 'Default';
        $factory = new Nag_Factory_Tasklists($this->getInjector());
        $this->assertInstanceOf(
            'Nag_Tasklists_Default',
            $factory->create()
        );
    }

    public function testCachedDefinition()
    {
        $GLOBALS['conf']['tasklists']['driver'] = 'Default';
        $factory = new Nag_Factory_Tasklists($this->getInjector());
        $initial = $factory->create();
        $this->assertSame(
            $initial, $factory->create()
        );
    }
}
