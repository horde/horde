<?php
/**
 * Basic Nag test case.
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
 * Basic Nag test case.
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
class Nag_TestCase
extends PHPUnit_Framework_TestCase
{
    static protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    static protected function createSqlPdoSqlite(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                'Horde_Db_Adapter' => array(
                    'factory' => 'Db',
                    'params' => array(
                        'migrations' => array(
                            'migrationsPath' => __DIR__ . '/../../migration',
                            'schemaTableName' => 'nag_test_schema'
                        )
                    )
                ),
            )
        );
    }

    static protected function createBasicNagSetup(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                '_PARAMS' => array(
                    'user' => 'test@example.com',
                    'app' => 'nag'
                ),
                'Horde_Alarm' => 'Alarm',
                'Horde_Prefs' => 'Prefs',
                'Horde_Perms' => 'Perms',
                'Horde_Group' => 'Group',
                'Horde_History' => 'History',
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

        $GLOBALS['conf']['prefs']['driver'] = 'Null';
    }

    static protected function createSqlShares(Horde_Test_Setup $setup)
    {
        $setup->getInjector()->setInstance(
            'Horde_Core_Factory_Db',
            new Horde_Test_Stub_Factory(
                $setup->getInjector()->getInstance('Horde_Db_Adapter')
            )
        );
        $setup->setup(
            array(
                'Horde_Share_Base' => 'Share',
            )
        );
        $setup->makeGlobal(
            array(
                'nag_shares' => 'Horde_Share_Base',
            )
        );
        $GLOBALS['conf']['storage']['driver'] = 'sql';
        $GLOBALS['conf']['tasklists']['driver'] = 'default';
    }

    static protected function createKolabShares(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                'Horde_Kolab_Storage' => array(
                    'factory' => 'KolabStorage',
                    'params' => array(
                        'imapuser' => 'test',
                    )
                ),
                'Horde_Share_Base' => array(
                    'factory' => 'Share',
                    'method' => 'Kolab',
                ),
            )
        );
        $setup->makeGlobal(
            array(
                'nag_shares' => 'Horde_Share_Base',
            )
        );
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $GLOBALS['conf']['tasklists']['driver'] = 'kolab';
    }

    static protected function createKolabSetup()
    {
        $setup = new Horde_Test_Setup();
        self::createBasicNagSetup($setup);
        self::createKolabShares($setup);
        self::_createDefaultShares();
       
        return $setup;
    }

    static protected function _createDefaultShares()
    {
        $share = self::_createShare(
            'Tasklist of Tester', 'test@example.com'
        );
        $other_share = self::_createShare(
            'Other tasklist of Tester', 'test@example.com'
        );
        return array($share, $other_share);
    }

    static private function _createShare($name, $owner)
    {
        $share = $GLOBALS['nag_shares']->newShare(
            $owner, strval(new Horde_Support_Randomid()), $name
        );
        $GLOBALS['nag_shares']->addShare($share);
        return $share;
    }
}