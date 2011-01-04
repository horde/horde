<?php
/**
 * Basic test case.
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
 * Basic test case.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function completeFactory($factory)
    {
        if ($factory === null) {
            return new Horde_Kolab_Storage_Factory();
        }
        return $factory;
    }

    protected function getNullMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory
        );
    }

    protected function getNullList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getNullMock($factory),
            $factory
        );
    }

    protected function getEmptyMock()
    {
        return new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(),
            $this->getEmptyAccount()
        );
    }

    protected function getEmptyAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array()
        );
    }

    protected function getTwoFolderAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array(
                'user/test' => null,
                'user/test/a' => null
            )
        );
    }

    protected function getTwoFolderMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getTwoFolderAccount()
        );
    }

    protected function getTwoFolderList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        return new Horde_Kolab_Storage_List_Base(
            $this->getTwoFolderMock($factory),
            $factory
        );
    }

    protected function getAnnotatedAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array(
                'user/test' => null,
                'user/test/a' => null,
                'user/test/Calendar' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'event.default',
                    )
                ),
                'user/test/Contacts' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'contact.default',
                    )
                ),
                'user/test/Notes' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'note.default',
                    )
                ),
                'user/test/Tasks' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'task.default',
                    )
                ),
            )
        );
    }

    protected function getAnnotatedMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getAnnotatedAccount()
        );
    }

    protected function getAnnotatedList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock($factory),
            $factory
        );
    }

    protected function getGermanAnnotatedAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => array(
                'user/test' => null,
                'user/test/Test' => null,
                'user/test/Kalender' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'event.default',
                    )
                ),
                'user/test/Kontakte' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'contact.default',
                    )
                ),
                'user/test/Notizen' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'note.default',
                    )
                ),
                'user/test/Aufgaben' => array(
                    'annotations' => array(
                        '/shared/vendor/kolab/folder-type' => 'task.default',
                    )
                ),
            )
        );
    }

    protected function getGermanAnnotatedMock()
    {
        return new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(),
            $this->getGermanAnnotatedAccount()
        );
    }

    protected function getCachedQueryForList($bare_list, $factory)
    {
        $list_cache = $this->getMockListCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $bare_list,
            $list_cache
        );
        $query = new Horde_Kolab_Storage_List_Query_Cache(
            $list,
            array(
                'factory' => $factory,
                'cache' => $list_cache
            )
        );
        $list->registerQuery('test', $query);
        $list->synchronize();
        return $query;
    }

    protected function getMockDriverList()
    {
        $this->mockDriver = $this->getMock('Horde_Kolab_Storage_Driver');
        return new Horde_Kolab_Storage_List_Base(
            $this->mockDriver,
            new Horde_Kolab_Storage_Factory()
        );
    }

    protected function getMockLogger()
    {
        $this->logHandler = new Horde_Log_Handler_Mock();
        return new Horde_Log_Logger($this->logHandler);
    }

    protected function getMockCache()
    {
        return new Horde_Kolab_Storage_Cache(new Horde_Cache_Storage_Mock());
    }

    protected function getMockListCache()
    {
        return new Horde_Kolab_Storage_Cache_List(
            $this->getMockCache()
        );
    }

    protected function assertLogCount($count)
    {
        $this->assertEquals(count($this->logHandler->events), $count);
    }

    protected function assertLogContains($message)
    {
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (strstr($event['message'], $message) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    protected function assertLogRegExp($regular_expression)
    {
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (preg_match($regular_expression, $event['message'], $matches) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
