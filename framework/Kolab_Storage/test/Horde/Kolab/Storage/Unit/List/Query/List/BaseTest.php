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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the basic list query.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_Query_List_BaseTest
extends PHPUnit_Framework_TestCase
{
    public function testListTypes()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('a' => 'a')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('a')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('A'));
        $this->assertEquals(
            array('a' => 'A'),
            $list->listTypes()
        );
    }

    public function testListByType()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('a' => 'a')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('a')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('A'));
        $this->assertEquals(array('a'), $list->listByType('A'));
    }

    public function testDataByType()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'a')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('a')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $namespace->expects($this->once())
            ->method('getTitle')
            ->with('INBOX/Test')
            ->will($this->returnValue('Test'));
        $namespace->expects($this->once())
            ->method('getSubpath')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX/Test'));
        $namespace->expects($this->once())
            ->method('getParent')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX'));
        $namespace->expects($this->exactly(3))
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $ns->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(''));
        $ns->expects($this->once())
            ->method('getDelimiter')
            ->will($this->returnValue('/'));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('private'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array(
                'INBOX/Test' => array(
                    'folder' => 'INBOX/Test',
                    'type' => 'test',
                    'default' => true,
                    'owner' => 'owner',
                    'name' => 'Test',
                    'subpath' => 'INBOX/Test',
                    'parent' => 'INBOX',
                    'namespace' => 'private',
                    'prefix' => '',
                    'delimiter' => '/',
                )
            ),
            $list->dataByType('test')
        );
    }

    public function testFolderData()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'a')));
        $this->driver->expects($this->once())
            ->method('listFolders')
            ->will($this->returnValue(array('INBOX/Test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('a')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $namespace->expects($this->once())
            ->method('getTitle')
            ->with('INBOX/Test')
            ->will($this->returnValue('Test'));
        $namespace->expects($this->once())
            ->method('getSubpath')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX/Test'));
        $namespace->expects($this->once())
            ->method('getParent')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX'));
        $namespace->expects($this->exactly(3))
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $ns->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(''));
        $ns->expects($this->once())
            ->method('getDelimiter')
            ->will($this->returnValue('/'));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('private'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array(
                'folder' => 'INBOX/Test',
                'type' => 'test',
                'default' => true,
                'owner' => 'owner',
                'name' => 'Test',
                'subpath' => 'INBOX/Test',
                'parent' => 'INBOX',
                'namespace' => 'private',
                'prefix' => '',
                'delimiter' => '/',
            ),
            $list->folderData('INBOX/Test')
        );
    }

    public function testMailFolderData()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array()));
        $this->driver->expects($this->once())
            ->method('listFolders')
            ->will($this->returnValue(array('INBOX/Test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('mail')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('mail'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(false));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $namespace->expects($this->once())
            ->method('getTitle')
            ->with('INBOX/Test')
            ->will($this->returnValue('Test'));
        $namespace->expects($this->once())
            ->method('getSubpath')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX/Test'));
        $namespace->expects($this->once())
            ->method('getParent')
            ->with('INBOX/Test')
            ->will($this->returnValue('INBOX'));
        $namespace->expects($this->exactly(3))
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $ns->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(''));
        $ns->expects($this->once())
            ->method('getDelimiter')
            ->will($this->returnValue('/'));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('private'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array(
                'folder' => 'INBOX/Test',
                'type' => 'mail',
                'default' => false,
                'owner' => 'owner',
                'name' => 'Test',
                'subpath' => 'INBOX/Test',
                'parent' => 'INBOX',
                'namespace' => 'private',
                'prefix' => '',
                'delimiter' => '/',
            ),
            $list->folderData('INBOX/Test')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testMissingFolderData()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listFolders')
            ->will($this->returnValue(array('INBOX/Test')));
        $list->folderData('INBOX/NO');
    }

    public function testListOwners()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listFolders')
            ->will($this->returnValue(array('INBOX/Test')));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array('INBOX/Test' => 'owner'),
            $list->listOwners()
        );
    }

    public function testListPersonalDefaults()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('test')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Horde_Kolab_Storage_Folder_Namespace::PERSONAL));
        $namespace->expects($this->once())
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array('test' => 'INBOX/Test'),
            $list->listPersonalDefaults()
        );
    }

    public function testSetDefault()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('setAnnotation')
            ->with(
                'INBOX/Foo',
                Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE,
                'event.default'
            );

        $this->driver->expects($this->exactly(2))
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Foo' => 'event')));
        $this->types->expects($this->exactly(2))
            ->method('create')
            ->with('event')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));

        $list->setDefault('INBOX/Foo');
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testSetDefaultFailsWithoutPreviousType()
    {
        $list = $this->_getList();

        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Bar' => 'event')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('event')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));

        $list->setDefault('INBOX/Foo');
    }

    public function testSetDefaultResetPreviousDefault()
    {
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $this->types = new Horde_Kolab_Storage_Folder_Types();
        $list = new Horde_Kolab_Storage_List_Query_List_Base(
            $this->driver,
            $this->types,
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );

        $this->driver->expects($this->exactly(2))
            ->method('setAnnotation')
            ->with(
                $this->logicalOr(
                    'INBOX/Foo',
                    'INBOX/Bar'
                ),
                Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE,
                $this->logicalOr(
                    'event.default',
                    'event'
                )
            );

        $this->driver->expects($this->exactly(2))
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will(
                $this->returnValue(
                    array(
                        'INBOX/Foo' => 'event',
                        'INBOX/Bar' => 'event.default'
                    )
                )
            );

        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Horde_Kolab_Storage_Folder_Namespace::PERSONAL));
        $namespace->expects($this->once())
            ->method('matchNamespace')
            ->will($this->returnValue($ns));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));

        $list->setDefault('INBOX/Foo');
    }


    public function testListDefaults()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('test')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Horde_Kolab_Storage_Folder_Namespace::PERSONAL));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            array('owner' => array('test' => 'INBOX/Test')),
            $list->listDefaults()
        );
    }

    public function testGetDefault()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('test')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Horde_Kolab_Storage_Folder_Namespace::PERSONAL));
        $namespace->expects($this->once())
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            'INBOX/Test',
            $list->getDefault('test')
        );
    }

    public function testGetForeignDefault()
    {
        $list = $this->_getList();
        $this->driver->expects($this->once())
            ->method('listAnnotation')
            ->with(Horde_Kolab_Storage_List_Query_List_Base::ANNOTATION_FOLDER_TYPE)
            ->will($this->returnValue(array('INBOX/Test' => 'test')));
        $this->types->expects($this->once())
            ->method('create')
            ->with('test')
            ->will($this->returnValue($this->mock_type));
        $this->mock_type->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('test'));
        $this->mock_type->expects($this->once())
            ->method('isDefault')
            ->will($this->returnValue(true));
        $ns = $this->getMock('Horde_Kolab_Storage_Folder_Namespace_Element', array(), array('A', 'B', 'C'));
        $ns->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Horde_Kolab_Storage_Folder_Namespace::PERSONAL));
        $namespace = $this->getMock('Horde_Kolab_Storage_Folder_Namespace', array(), array(array()));
        $namespace->expects($this->once())
            ->method('getOwner')
            ->with('INBOX/Test')
            ->will($this->returnValue('owner'));
        $namespace->expects($this->once())
            ->method('matchNamespace')
            ->with('INBOX/Test')
            ->will($this->returnValue($ns));
        $this->driver->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->assertEquals(
            'INBOX/Test',
            $list->getForeignDefault('owner', 'test')
        );
    }

    private function _getList()
    {
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $this->types = $this->getMock('Horde_Kolab_Storage_Folder_Types');
        $this->mock_type = $this->getMock('Horde_Kolab_Storage_Folder_Type', array(), array('event.default'));
        return new Horde_Kolab_Storage_List_Query_List_Base(
            $this->driver,
            $this->types,
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }
}
