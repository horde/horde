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
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Basic Nag test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Nag_TestCase
extends PHPUnit_Framework_TestCase
{
    static protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    static private function _setupDefaultGlobals()
    {
        $GLOBALS['registry'] = new Nag_Stub_Registry();
        $GLOBALS['injector'] = self::getInjector();
        $GLOBALS['injector']->setInstance('Horde_History', new Horde_History_Mock('test@example.com'));
        $GLOBALS['injector']->setInstance('Horde_Group', new Horde_Group_Mock());
        $GLOBALS['conf']['prefs']['driver'] = 'Null';
    }

    static protected function getKolabDriver()
    {
        self::_setupDefaultGlobals();
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $storage = self::createKolabStorage();
        $GLOBALS['injector']->setInstance('Horde_Kolab_Storage', $storage);
        $GLOBALS['nag_shares'] = self::createKolabShares($storage);
        list($share, $other_share) = self::_createDefaultShares();
        return new Nag_Driver_Kolab($share->getName());
    }

    static protected function getSqlDriver(Horde_Db_Adapter $db)
    {
        self::_setupDefaultGlobals();
        $GLOBALS['conf']['storage']['driver'] = 'sql';
        $GLOBALS['injector']->setInstance(
            'Horde_Core_Factory_Db',
            new Nag_Stub_DbFactory($db)
        );
        $GLOBALS['nag_shares'] = self::createSqlShares($db);
        list($share, $other_share) = self::_createDefaultShares();
        return new Nag_Driver_Sql(
            $share->getName(), array('charset' => 'UTF-8')
        );
    }

    static public function createKolabStorage()
    {
        $kolab_factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => array(
                    'username' => 'test@example.com',
                    'host' => 'localhost',
                    'port' => 143,
                    'data' => array(
                        'user/test' => array(
                            'permissions' => array('anyone' => 'alrid')
                        )
                    )
                )
            )
        );
        return $kolab_factory->create();
    }

    static public function createKolabShares(Horde_Kolab_Storage $storage)
    {
        $shares = new Horde_Share_Kolab(
            'nag',
            'test@example.com',
            new Horde_Perms_Null(),
            new Horde_Group_Mock()
        );
        $shares->setStorage($storage);
        return $shares;
    }

    static public function createSqlShares(Horde_Db_Adapter $db)
    {
        $shares = new Horde_Share_Sqlng(
            'nag',
            'test@example.com',
            new Horde_Perms_Null(),
            new Horde_Group_Mock()
        );
        $shares->setStorage($db);
        return $shares;
    }

    static private function _createDefaultShares()
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