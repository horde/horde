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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getNullList($factory)
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
            $factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_List_Base',
                $list
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
            $factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_List_Base',
                $list
            )
        );
        return $list;
    }

    protected function getAnnotatedQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getAnnotatedList($factory)
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
            $factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_List_Base',
                $list
            )
        );
        return $list;
    }

    protected function getNamespaceQuery($factory = null)
    {
        $factory = $this->completeFactory($factory);
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getNamespaceList($factory)
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
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getForeignDefaultList($factory)
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
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getEventList($factory)
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
        return $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_List_Base', $this->getDoubleEventList($factory)
        );
    }

    protected function getMessageAccount()
    {
        return array(
            'username' => 'test@example.com',
            'data' => $this->getMockData(
                array(
                    'user/test' => null,
                    'user/test/a' => null,
                    'user/test/Test' => array('m' => array()),
                    'user/test/Empty' => array('m' => array()),
                    'user/test/ÄÖÜ' => array('m' => array()),
                    'user/test/Pretend' => array('m' => array(1 => array())),
                    'user/test/WithDeleted' => array(
                        'm' => array(
                            1 => array(
                                'flags' => Horde_Kolab_Storage_Driver_Mock::FLAG_DELETED
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
                            1 => $this->getDefaultEventData(),
                            2 => $this->getDefaultEventData(),
                            3 => array(
                                'flags' => Horde_Kolab_Storage_Driver_Mock::FLAG_DELETED
                            ),
                            4 => $this->getDefaultEventData(),
                        ),
                        's' => array(
                            'uidvalidity' => '12346789',
                            'uidnext' => 5
                        )

                    ),
                    'user/test/Contacts' => array('t' => 'contact.default'),
                    'user/test/Notes' => array('t' => 'note.default'),
                    'user/test/Tasks' => array('t' => 'task.default'),
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

    protected function getMessageStorage(
        $factory = null, $params = array()
    ) {
        return $this->completeFactory($factory)
            ->createFromParams(
                array_merge(
                    array(
                        'driver' => 'mock',
                        'params' => $this->getMessageAccount()
                    ),
                    $params
                )
            );
    }

    protected function getCachedQueryForList($bare_list, $factory)
    {
        $list_cache = $this->getMockListCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $bare_list,
            $list_cache
        );
        $query = new Horde_Kolab_Storage_List_Query_List_Cache(
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
        $cache = new Horde_Kolab_Storage_Cache_List(
            $this->getMockCache()
        );
        $cache->setListId('test');
        return $cache;
    }

    protected function getMockDataCache()
    {
        $cache = $this->getMockCache()->getDataCache(
            array(
                'host' => 'localhost',
                'port' => '143',
                'owner' => 'test',
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

    protected function getMockData($elements)
    {
        $result = array();
        foreach ($elements as $path => $element) {
            if (!isset($element['p'])) {
                $folder = array('permissions' => array('anyone' => 'alrid'));
            } else {
                $folder = array('permissions' => $element['p']);
            }
            if (isset($element['a'])) {
                $folder['annotations'] = $element['a'];
            }
            if (isset($element['t'])) {
                $folder['annotations'] = array(
                    '/shared/vendor/kolab/folder-type' => $element['t'],
                );
            }
            if (isset($element['m'])) {
                $keys = array_keys($element['m']);
                $folder['status'] = array(
                    'uidvalidity' => time(),
                    'uidnext' => empty($keys) ? 1 : max($keys) + 1
                );
                $folder['mails'] = $element['m'];
                foreach ($element['m'] as $uid => $mail) {
                    if (isset($mail['structure'])) {
                        $folder['mails'][$uid]['structure'] = unserialize(
                            base64_decode(file_get_contents($mail['structure']))
                        );
                    }
                    if (isset($mail['parts'])) {
                        $folder['mails'][$uid]['structure']['parts'] = $mail['parts'];
                    }
                }
            } else {
                $folder['status'] = array(
                    'uidvalidity' => time(),
                    'uidnext' => 1
                );
            }
            if (isset($element['s'])) {
                $folder['status'] = $element['s'];
            }
            $result[$path] = $folder;
        }
        return $result;
    }

    protected function getDefaultEventData()
    {
        return array(
            'structure' => dirname(__FILE__) . '/fixtures/event.struct',
            'parts' => array(
                '2' => array(
                    'file' => dirname(__FILE__) . '/fixtures/event.xml.qp',
                )
            )
        );
    }
}
