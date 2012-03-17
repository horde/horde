<?php
/**
 * Basic Ansel test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL-2.0
 * @link       http://www.horde.org/apps/ansel
 */

/**
 * Basic Ansel test case.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL-2.0
 * @link       http://www.horde.org/apps/ansel
 */
class Ansel_TestCase
extends PHPUnit_Framework_TestCase
{
    static protected function createBasicAnselSetup(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                '_PARAMS' => array(
                    'user' => 'test@example.com',
                    'app' => 'ansel'
                ),
                // 'Horde_Core_Factory_Vfs' => array(
                //     'factory' => 'Ansel_Unit_Factory_Vfs',
                //     'method' => 'create')
                'Horde_Prefs' => 'Prefs',
                //'Horde_Perms' => 'Perms',
                //'Horde_Group' => 'Group',
                'Horde_Registry' => 'Registry',
            )
        );

        $setup->makeGlobal(
            array(
                'prefs' => 'Horde_Prefs',
                'registry' => 'Horde_Registry',
                'injector' => 'Horde_Injector',
            )
        );

        // TODO: need separate test bundles
       $GLOBALS['conf']['image']['driver'] = 'Gd';
       $GLOBALS['conf']['image']['type'] = 'jpg';
       $GLOBALS['conf']['exif']['driver'] = 'Bundled';
   }

   static protected function createTestVFS(Horde_Test_Setup $setup)
    {
        $setup->getInjector()->setInstance(
            'Ansel_Vfs',
            new Horde_Vfs_File(array('vfsroot' => __DIR__ . '/fixtures/vfs'))
        );

        $setup->getInjector()->setInstance(
            'Horde_Core_Factory_Vfs',
            new Horde_Test_Stub_Factory(
                $setup->getInjector()->getInstance('Ansel_Vfs')
            )
        );
    }

}