<?php
/**
 * Test the basic list query.
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
 * Test the basic list query.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_List_Query_List_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testByTypeReturnsArray()
    {
        $this->assertType('array', $this->getNullQuery()->listByType('test'));
    }

    public function testListCalendarsListsCalendars()
    {
        $this->assertEquals(
            array('INBOX/Calendar'),
            $this->getAnnotatedQuery()->listByType('event')
        );
    }

    public function testListTasklistsListsTasklists()
    {
        $this->assertEquals(
            array('INBOX/Tasks'),
            $this->getAnnotatedQuery()->listByType('task')
        );
    }

    public function testTypeReturnsArray()
    {
        $this->assertType('array', $this->getNullQuery()->listTypes());
    }

    public function testTypeReturnsAnnotations()
    {
        $this->assertEquals(
            array(
                'INBOX/Calendar' => 'event',
                'INBOX/Contacts' => 'contact',
                'INBOX/Notes' => 'note',
                'INBOX/Tasks' => 'task',
            ),
            $this->getAnnotatedQuery()->listTypes()
        );
    }

    public function testAnnotationsReturnsHandlers()
    {
        $query = $this->getAnnotatedQuery();
        foreach ($query->listFolderTypeAnnotations() as $folder => $type) {
            $this->assertInstanceOf('Horde_Kolab_Storage_Folder_Type', $type);
        };
    }

    public function testListOwnersReturn()
    {
        $this->assertType(
            'array',
            $this->getAnnotatedQuery()->listOwners()
        );
    }

    public function testListOwnerList()
    {
        $this->assertEquals(
            array(
                'INBOX' => 'test@example.com',
                'INBOX/Calendar' => 'test@example.com',
                'INBOX/Contacts' => 'test@example.com',
                'INBOX/Notes' => 'test@example.com',
                'INBOX/Tasks' => 'test@example.com',
                'INBOX/a' => 'test@example.com',
            ),
            $this->getAnnotatedQuery()->listOwners()
        );
    }

    public function testListOwnerNamespace()
    {
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
            $this->getNamespaceQuery()->listOwners()
        );
    }

    public function testDefaultReturn()
    {
        $this->assertType(
            'string',
            $this->getNamespaceQuery()->getDefault('event')
        );
    }

    public function testDefaultCalendar()
    {
        $this->assertEquals(
            'INBOX/Calendar',
            $this->getAnnotatedQuery()->getDefault('event')
        );
    }

    public function testDefaultNotes()
    {
        $this->assertEquals(
            'INBOX/Notes',
            $this->getAnnotatedQuery()->getDefault('note')
        );
    }

    public function testMissingDefault()
    {
        $this->assertFalse(
            $this->getNullQuery()->getDefault('note')
        );
    }

    public function testIgnoreForeignDefault()
    {
        $this->assertFalse(
            $this->getForeignDefaultQuery()->getDefault('event')
        );
    }

    public function testIdentifyDefault()
    {
        $this->assertEquals(
            'INBOX/Events',
            $this->getEventQuery()->getDefault('event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testBailOnDoubleDefault()
    {
        $this->getDoubleEventQuery()->getDefault('event');
    }

    public function testForeignDefaultReturn()
    {
        $this->assertType(
            'string',
            $this->getEventQuery()->getForeignDefault(
                'someone@example.com', 'event'
            )
        );
    }

    public function testForeignDefaultCalendar()
    {
        $this->assertEquals(
            'user/someone/Calendar',
            $this->getEventQuery()->getForeignDefault(
                'someone@example.com', 'event'
            )
        );
    }

    public function testForeignDefaultNotes()
    {
        $this->assertEquals(
            'user/someone/Notes',
            $this->getEventQuery()->getForeignDefault(
                'someone@example.com', 'note'
            )
        );
    }

    public function testMissingForeignDefault()
    {
        $this->assertFalse(
            $this->getNullQuery()->getForeignDefault(
                'someone@example.com', 'contact'
            )
        );
    }

    public function testIdentifyForeignDefault()
    {
        $this->assertEquals(
            'user/someone/Calendar',
            $this->getEventQuery()->getForeignDefault(
                'someone@example.com', 'event'
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testBailOnDoubleForeignDefault()
    {
        $this->getDoubleEventQuery()->getForeignDefault(
            'someone@example.com', 'event'
        );
    }

    public function testListPersonalDefaults()
    {
        $this->assertEquals(
            array(
                'contact' => 'INBOX/Contacts',
                'event' => 'INBOX/Calendar',
                'note' => 'INBOX/Notes',
                'task' => 'INBOX/Tasks'
            ),
            $this->getAnnotatedQuery()->listPersonalDefaults()
        );
    }

    public function testListDefaults()
    {
        $this->assertEquals(
            array(
                'example@example.com' => array(
                    'event' => 'user/example/Calendar'
                ),
                'someone@example.com' => array(
                    'event' => 'user/someone/Calendars/Events'
                )
            ),
            $this->getForeignDefaultQuery()->listDefaults()
        );
    }

    public function testDataByTypeReturnsArray()
    {
        $this->assertType('array', $this->getNullQuery()->dataByType('test'));
    }

    public function testListCalendarsListsCalendarData()
    {
        $this->assertEquals(
            array('INBOX/Calendar'),
            array_keys($this->getAnnotatedQuery()->dataByType('event'))
        );
    }

    public function testListTasklistsListsTasklistData()
    {
        $this->assertEquals(
            array('INBOX/Tasks'),
            array_keys($this->getAnnotatedQuery()->dataByType('task'))
        );
    }

    public function testListDataHasOwner()
    {
        $data = $this->getAnnotatedQuery()->dataByType('event');
        $this->assertEquals(
            'test@example.com',
            $data['INBOX/Calendar']['owner']
        );
    }

    public function testListDataHasTitle()
    {
        $data = $this->getAnnotatedQuery()->dataByType('event');
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
        $this->assertType('array', $this->getAnnotatedQuery()->folderData('INBOX/Calendar'));
    }

    public function testFolderDataHasOwner()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertEquals(
            'test@example.com',
            $data['owner']
        );
    }

    public function testFolderDataHasTitle()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertEquals(
            'Calendar',
            $data['name']
        );
    }

    public function testFolderDataHasType()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertEquals(
            'event',
            $data['type']
        );
    }

    public function testFolderDataHasDefault()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertTrue(
            $data['default']
        );
    }

    public function testMailFolderDataType()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX');
        $this->assertEquals(
            'mail',
            $data['type']
        );
    }

    public function testMailFolderDataNoDefault()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX');
        $this->assertFalse(
            $data['default']
        );
    }

    public function testFolderDataHasNamespace()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertEquals(
            'personal',
            $data['namespace']
        );
    }

    public function testFolderDataHasSubpath()
    {
        $data = $this->getAnnotatedQuery()->folderData('INBOX/Calendar');
        $this->assertEquals(
            'Calendar',
            $data['subpath']
        );
    }
}
