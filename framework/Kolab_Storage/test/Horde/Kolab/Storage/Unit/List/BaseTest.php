<?php
/**
 * Test the basic folder list handler.
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
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the basic folder list handler.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_List_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListReturnsArray()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertType('array', $list->listFolders());
    }

    public function testListReturnsFolders()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getTwoFolderMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertEquals(
            array('INBOX', 'INBOX/a'),
            $list->listFolders()
        );
    }

    public function testTypeReturnsArray()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertType('array', $list->listTypes());
    }

    public function testTypeReturnsAnnotations()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertEquals(
            array(
                'INBOX/Calendar' => 'event',
                'INBOX/Contacts' => 'contact',
                'INBOX/Notes' => 'note',
                'INBOX/Tasks' => 'task',
            ),
            $list->listTypes()
        );
    }

    public function testAnnotationsReturnsHandlers()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock(),
            new Horde_Kolab_Storage_Factory()
        );
        foreach ($list->listFolderTypeAnnotations() as $folder => $type) {
            $this->assertInstanceOf('Horde_Kolab_Storage_Folder_Type', $type);
        };
    }

    public function testListQueriable()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertTrue($list instanceOf Horde_Kolab_Storage_Queriable);
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetQueryForUnsupported()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $list->getQuery('NO_SUCH_QUERY');
    }

    public function testQueryReturnsQuery()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertInstanceOf('Horde_Kolab_Storage_Query', $list->getQuery('Base'));
    }
}
