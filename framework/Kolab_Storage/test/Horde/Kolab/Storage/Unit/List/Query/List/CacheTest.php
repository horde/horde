<?php
/**
 * Test the cached list query.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the cached list query.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_List_Query_List_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testAnotationsReturnsArray()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertType('array', $query->listFolderTypeAnnotations());
    }

    public function testAnnotationsReturnsHandlers()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        foreach ($query->listFolderTypeAnnotations() as $folder => $type) {
            $this->assertInstanceOf('Horde_Kolab_Storage_Folder_Type', $type);
        };
    }

    public function testTypeReturnsArray()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertType('array', $query->listTypes());
    }

    public function testTypeReturnsAnnotations()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(
            array(
                'INBOX' => 'mail',
                'INBOX/Calendar' => 'event',
                'INBOX/Contacts' => 'contact',
                'INBOX/Notes' => 'note',
                'INBOX/Tasks' => 'task',
                'INBOX/a' => 'mail',
            ),
            $query->listTypes()
        );
    }

    public function testByTypeReturnsArray()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertType('array', $query->listByType('test'));
    }

    public function testListCalendarsListsCalendars()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(array('INBOX/Calendar'), $query->listByType('event'));
    }

    public function testListTasklistsListsTasklists()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(array('INBOX/Tasks'), $query->listByType('task'));
    }

    public function testListOwnersReturn()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertType(
            'array',
            $query->listOwners()
        );
    }

    public function testListOwnerList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(
            array(
                'INBOX' => 'test@example.com',
                'INBOX/Calendar' => 'test@example.com',
                'INBOX/Contacts' => 'test@example.com',
                'INBOX/Notes' => 'test@example.com',
                'INBOX/Tasks' => 'test@example.com',
                'INBOX/a' => 'test@example.com',
            ),
            $query->listOwners()
        );
    }

    public function testListOwnerNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNamespaceList($factory), $factory);
        $this->assertEquals(
            array(
                'INBOX' => 'test@example.com',
                'INBOX/Calendar' => 'test@example.com',
                'INBOX/Contacts' => 'test@example.com',
                'INBOX/Notes' => 'test@example.com',
                'INBOX/Tasks' => 'test@example.com',
                'INBOX/a' => 'test@example.com',
                'shared.Calendars/All' => 'anonymous',
                'shared.Calendars/Others' => 'anonymous',
                'user/example/Calendar' => 'example@example.com',
                'user/example/Notes' => 'example@example.com',
                'user/someone/Calendars/Events' => 'someone@example.com',
                'user/someone/Calendars/Party' => 'someone@example.com',
            ),
            $query->listOwners()
        );
    }

    public function testDefaultReturn()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertType(
            'string',
            $query->getDefault('event')
        );
    }

    public function testDefaultCalendar()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(
            'INBOX/Calendar',
            $query->getDefault('event')
        );
    }

    public function testDefaultNotes()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(
            'INBOX/Notes',
            $query->getDefault('note')
        );
    }

    public function testMissingDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertFalse(
            $query->getDefault('note')
        );
    }

    public function testIgnoreForeignDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getForeignDefaultList($factory), $factory);
        $this->assertFalse(
            $query->getDefault('event')
        );
    }

    public function testIdentifyDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getEventList($factory), $factory);
        $this->assertEquals(
            'INBOX/Events',
            $query->getDefault('event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testBailOnDoubleDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getDoubleEventList($factory), $factory);
        $query->getDefault('event');
    }

    public function testForeignDefaultReturn()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getEventList($factory), $factory);
        $this->assertType(
            'string',
            $query->getForeignDefault('someone@example.com', 'event')
        );
    }

    public function testForeignDefaultCalendar()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getEventList($factory), $factory);
        $this->assertEquals(
            'user/someone/Calendar',
            $query->getForeignDefault('someone@example.com', 'event')
        );
    }

    public function testForeignDefaultNotes()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getEventList($factory), $factory);
        $this->assertEquals(
            'user/someone/Notes',
            $query->getForeignDefault('someone@example.com', 'note')
        );
    }

    public function testMissingForeignDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertFalse(
            $query->getForeignDefault('someone@example.com', 'contact')
        );
    }

    public function testIdentifyForeignDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getEventList($factory), $factory);
        $this->assertEquals(
            'user/someone/Calendar',
            $query->getForeignDefault('someone@example.com', 'event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testBailOnDoubleForeignDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getDoubleEventList($factory), $factory);
        $query->getForeignDefault('someone@example.com', 'event');
    }

    public function testListPersonalDefaults()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(
            array(
                'contact' => 'INBOX/Contacts',
                'event' => 'INBOX/Calendar',
                'note' => 'INBOX/Notes',
                'task' => 'INBOX/Tasks'
            ),
            $query->listPersonalDefaults()
        );
    }

    public function testListDefaults()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getForeignDefaultList($factory), $factory);
        $this->assertEquals(
            array(
                'example@example.com' => array(
                    'event' => 'user/example/Calendar'
                ),
                'someone@example.com' => array(
                    'event' => 'user/someone/Calendars/Events'
                )
            ),
            $query->listDefaults()
        );
    }

    public function testDataByTypeReturnsArray()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getNullList($factory), $factory);
        $this->assertType('array', $query->dataByType('test'));
    }

    public function testListCalendarsListsCalendarData()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(array('INBOX/Calendar'), array_keys($query->dataByType('event')));
    }

    public function testListTasklistsListsTasklistData()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $this->assertEquals(array('INBOX/Tasks'), array_keys($query->dataByType('task')));
    }

    public function testListDataHasOwner()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $data = $query->dataByType('event');
        $this->assertEquals(
            'test@example.com',
            $data['INBOX/Calendar']['owner']
        );
    }

    public function testListDataHasTitle()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $query = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory);
        $data = $query->dataByType('event');
        $this->assertEquals(
            'Calendar',
            $data['INBOX/Calendar']['name']
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingFolderData()
    {
        $this->assertType('array', $this->getNullQuery()->folderData('INBOX/Calendar'));
    }

    public function testFolderDataReturnsArray()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertType('array', $data);
    }

    public function testFolderDataHasOwner()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertEquals(
            'test@example.com',
            $data['owner']
        );
    }

    public function testFolderDataHasTitle()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertEquals(
            'Calendar',
            $data['name']
        );
    }

    public function testFolderDataHasType()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertEquals(
            'event',
            $data['type']
        );
    }

    public function testFolderDataHasDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertTrue(
            $data['default']
        );
    }

    public function testMailFolderDataType()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX');
        $this->assertEquals(
            'mail',
            $data['type']
        );
    }

    public function testMailFolderDataNoDefault()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX');
        $this->assertFalse(
            $data['default']
        );
    }

    public function testFolderDataHasNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertEquals(
            'personal',
            $data['namespace']
        );
    }

    public function testFolderDataHasSubpath()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $data = $this->getCachedQueryForList($this->getAnnotatedList($factory), $factory)->folderData('INBOX/Calendar');
        $this->assertEquals(
            'Calendar',
            $data['subpath']
        );
    }
}
