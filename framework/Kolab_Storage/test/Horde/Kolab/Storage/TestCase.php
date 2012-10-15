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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Basic test case.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_TestCase
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SESSION = array();
    }

    public function tearDown()
    {
        $_SESSION = array();
    }

    protected function completeFactory($factory = null)
    {
        if ($factory === null) {
            return new Horde_Kolab_Storage_Factory();
        }
        return $factory;
    }

    protected function createStorage(
        $driver = null,
        $factory = null,
        $params = array()
    ) {
        $factory = $this->completeFactory($factory);
        if ($driver === null) {
            $driver = new Horde_Kolab_Storage_Driver_Mock($factory);
        }
        return new Horde_Kolab_Storage_Uncached(
            $driver,
            new Horde_Kolab_Storage_QuerySet_Uncached($factory),
            $factory,
            $this->getMock('Horde_Kolab_Storage_Cache', array(), array(), '', false, false),
            $this->getMock('Horde_Log_Logger'),
            $params
        );
    }

    protected function createCachedStorage($driver = null, $factory = null)
    {
        $factory = $this->completeFactory($factory);
        if ($driver === null) {
            $driver = new Horde_Kolab_Storage_Driver_Mock(
                $factory,
                array('username' => 'test', 'host' => 'localhost', 'port' => 143)
            );
        }
        $cache = $this->getMockCache();
        return new Horde_Kolab_Storage_Cached(
            $driver,
            new Horde_Kolab_Storage_QuerySet_Cached($factory, array(), $cache),
            $factory,
            $cache,
            $this->getMock('Horde_Log_Logger')
        );
    }

    protected function getNullMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            array('username' => 'test', 'host' => 'localhost', 'port' => 143)
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

    protected function getNullQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getNullMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
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
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/a' => null
                )
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
            'host' => 'localhost',
            'port' => 143,
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/a' => null,
                    'user/test/Calendar' => array('t' => 'event.default'),
                    'user/test/Contacts' => array('t' => 'contact.default'),
                    'user/test/Notes' => array('t' => 'note.default'),
                    'user/test/Tasks' => array('t' => 'task.default'),
                )
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

    protected function getAnnotatedQueriableList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        $list = $this->getAnnotatedList($factory);
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE,
            new Horde_Kolab_Storage_List_Query_List_Base(
                $list, array('factory' => $factory)
            )
        );
        return $list;
    }

    protected function getCachedAnnotatedQueriableList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getAnnotatedList($factory),
            $this->getMockListCache(),
            $factory
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE,
            new Horde_Kolab_Storage_List_Query_List_Base(
                $list, array('factory' => $factory)
            )
        );
        return $list;
    }

    protected function getAnnotatedQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getAnnotatedMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    protected function getGermanAnnotatedAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/Test' => null,
                    'user/test/Kalender' => array('t' => 'event.default'),
                    'user/test/Kontakte' => array('t' => 'contact.default'),
                    'user/test/Notizen' => array('t' => 'note.default'),
                    'user/test/Aufgaben' => array('t' => 'task.default'),
                )
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

    protected function getNamespaceAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/a' => null,
                    'user/test/Calendar' => array('t' => 'event.default'),
                    'user/test/Contacts' => array('t' => 'contact.default'),
                    'user/test/Notes' => array('t' => 'note.default'),
                    'user/test/Tasks' => array('t' => 'task.default'),
                    'user/example/Notes' => array('t' => 'note.default'),
                    'user/example/Calendar' => array('t' => 'event.default'),
                    'user/someone/Calendars/Events' => array('t' => 'event.default'),
                    'user/someone/Calendars/Party' => array('t' => 'event'),
                    'shared.Calendars/All' => array('t' => 'event'),
                    'shared.Calendars/Others' => array('t' => 'event'),
                )
            )
        );
    }

    protected function getNamespaceMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getNamespaceAccount()
        );
    }

    protected function getNamespaceList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getNamespaceMock($factory),
            $factory
        );
    }

    protected function getNamespaceQueriableList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        $list = $this->getNamespaceList($factory);
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE,
            new Horde_Kolab_Storage_List_Query_List_Base(
                $list, array('factory' => $factory)
            )
        );
        return $list;
    }

    protected function getNamespaceQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getNamespaceMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    protected function getForeignDefaultAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/example/Calendar' => array('t' => 'event.default'),
                    'user/someone/Calendars/Events' => array('t' => 'event.default'),
                )
            )
        );
    }

    protected function getForeignDefaultMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getForeignDefaultAccount()
        );
    }

    protected function getForeignDefaultList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getForeignDefaultMock($factory),
            $factory
        );
    }

    protected function getForeignDefaultQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getForeignDefaultMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    protected function getEventAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/Calendar' => array('t' => 'event'),
                    'user/test/Events' => array('t' => 'event.default'),
                    'user/test/Notes' => array('t' => 'note.default'),
                    'user/someone/Calendar' => array('t' => 'event.default'),
                    'user/someone/Events' => array('t' => 'event'),
                    'user/someone/Notes' => array('t' => 'note.default'),
                )
            )
        );
    }

    protected function getEventMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getEventAccount()
        );
    }

    protected function getEventList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getEventMock($factory),
            $factory
        );
    }

    protected function getEventQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getEventMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    protected function getDoubleEventAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/Calendar' => array('t' => 'event.default'),
                    'user/test/Events' => array('t' => 'event.default'),
                    'user/someone/Calendar' => array('t' => 'event.default'),
                    'user/someone/Events' => array('t' => 'event.default'),
                )
            )
        );
    }

    protected function getDoubleEventMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getDoubleEventAccount()
        );
    }

    protected function getDoubleEventList($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Base(
            $this->getDoubleEventMock($factory),
            $factory
        );
    }

    protected function getDoubleEventQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->getDoubleEventMock($factory),
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    protected function getDataAccount($additional_folders)
    {
        return array(
            'username' => 'test@example.com',
            'host' => 'localhost',
            'port' => 143,
            'data' => $this->getMockData(
                array_merge(
                    array(
                        'user/test' => null,
                        'user/test/a' => null,
                        'user/test/Test' => array('m' => array()),
                        'user/test/Empty' => array('m' => array()),
                        'user/test/ÄÖÜ' => array('m' => array()),
                        'user/test/Pretend' => array('m' => array(1 => array())),
                        'user/test/Contacts' => array('t' => 'contact.default'),
                        'user/test/Notes' => array('t' => 'note.default'),
                        'user/test/OtherNotes' => array('t' => 'note'),
                        'user/test/Tasks' => array('t' => 'task.default'),
                    ),
                    $additional_folders
                )
            )
        );
    }

    protected function getMessageAccount()
    {
        return $this->getDataAccount(
            array(
                'user/test/WithDeleted' => array(
                    'm' => array(
                        1 => array(
                            'flags' => Horde_Kolab_Storage_Driver_Mock_Data::FLAG_DELETED
                        ),
                        4 => array()
                    ),
                    's' => array(
                        'uidvalidity' => '12346789',
                        'uidnext' => 5
                    )
                ),
                'user/test/Calendar' => array(
                    't' => 'event.default',
                    'm' => array(
                        1 => $this->getDefaultEventData('.1'),
                        2 => $this->getDefaultEventData('.2'),
                        3 => array(
                            'flags' => Horde_Kolab_Storage_Driver_Mock_Data::FLAG_DELETED
                        ),
                        4 => $this->getDefaultEventData('.3'),
                    ),
                    's' => array(
                        'uidvalidity' => '12346789',
                        'uidnext' => 5
                    )
                )
            )
        );
    }

    protected function getMessageMock($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return new Horde_Kolab_Storage_Driver_Mock(
            $factory,
            $this->getMessageAccount()
        );
    }

    protected function getDataStorage(
        $data, $params = array()
    ) {
        $factory = new Horde_Kolab_Storage_Factory(
            array_merge(
                array(
                    'driver' => 'mock',
                    'params' => $data,
                    'logger' => $this->getMock('Horde_Log_Logger'),
                ),
                $params
            )
        );
        return $factory->create();
    }

    protected function getMessageStorage(
        $params = array()
    ) {
        return $this->getDataStorage(
            $this->getMessageAccount(),
            $params
        );
    }

    protected function getCachedQueryForList($driver)
    {
        return new Horde_Kolab_Storage_List_Query_List_Cache(
            new Horde_Kolab_Storage_List_Query_List_Cache_Synchronization(
                $driver,
                new Horde_Kolab_Storage_Folder_Types(),
                new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
            ),
            $this->getMockListCache()
        );
    }

    protected function getMockDriverList($factory = null)
    {
        $factory = $this->completeFactory($factory);
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
        return new Horde_Kolab_Storage_Cache(new Horde_Cache(new Horde_Cache_Storage_Mock()));
    }

    protected function getMockListCache()
    {
        $cache = new Horde_Kolab_Storage_List_Cache(
            $this->getMockCache(),
            array(
                'host' => 'localhost',
                'port' => '143',
                'user' => 'user',
            )
        );
        return $cache;
    }

    protected function getMockDataCache()
    {
        $cache = $this->getMockCache()->getDataCache(
            array(
                'host' => 'localhost',
                'port' => '143',
                'owner' => 'test',
                'prefix' => 'INBOX',
                'folder' => 'Calendar',
                'type' => 'event',
            )
        );
        $cache->setDataId('test');
        return $cache;
    }

    protected function assertLogCount($count)
    {
        $this->assertEquals(count($this->logHandler->events), $count);
    }

    protected function assertLogContains($message)
    {
        $messages = array();
        $found = false;
        foreach ($this->logHandler->events as $event) {
            if (strstr($event['message'], $message) !== false) {
                $found = true;
                break;
            }
            $messages[] = $event['message'];
        }
        $this->assertTrue($found, sprintf("Did not find \"%s\" in [\n%s\n]", $message, join("\n", $messages)));
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

    protected function getMockData($elements)
    {
        $elements['format'] = 'brief';
        return new Horde_Kolab_Storage_Driver_Mock_Data($elements);
    }

    protected function getDefaultEventData($add = '')
    {
        return array(
            'structure' => __DIR__ . '/fixtures/event.struct',
            'parts' => array(
                '2' => array(
                    'file' => __DIR__ . '/fixtures/event' . $add . '.xml.qp',
                )
            )
        );
    }
}
