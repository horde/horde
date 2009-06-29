<?php
/**
 * Test the Kolab data handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/DataTest.php,v 1.10 2009/01/14 23:39:14 wrobel Exp $
 *
 * @package Kolab_Storage
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/Data.php';
require_once 'Horde/Kolab/IMAP.php';
require_once 'Horde/Kolab/IMAP/test.php';

/**
 * Test the Kolab data handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/DataTest.php,v 1.10 2009/01/14 23:39:14 wrobel Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_DataTest extends Horde_Kolab_Test_Storage
{

    /**
     * Test setup.
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $this->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);
        $this->prepareNewFolder($world['storage'], 'NewContacts', 'contact');
    }

    /**
     * Test class constructor.
     */
    public function testConstruct()
    {
        $data = &new Kolab_Data('test');
        $this->assertEquals('test', $data->_object_type);
    }

    /**
     * Test cache access.
     */
    public function testGetCacheKey()
    {
        $data = &new Kolab_Data('test');
        $this->assertTrue($data->_cache_cyrus_optimize);
        $session = &Horde_Kolab_Session::singleton();
        $imap = &$session->getImap();
        $this->assertEquals('Horde_Kolab_IMAP_test', get_class($imap));
        
        $folder = &new Kolab_Folder('INBOX/Test');
        $data->setFolder($folder);
        $this->assertEquals('INBOX/Test', $data->_folder->name);
        $this->assertEquals('user/wrobel/Test', $data->_getCacheKey());
    }

    /**
     * Test object deletion.
     */
    public function testDelete()
    {
        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $data->setFolder($folder);

        /** 
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->_cache->expire();

        $result = $data->delete('1');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertFalse($result);
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $result = $data->delete('1');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $ids = $data->getObjectIds();
        if (is_a($ids, 'PEAR_Error')) {
            $this->assertEquals('', $ids->message);
        }
        $this->assertTrue(empty($ids));
    }

    /**
     * Test object moving.
     */
    public function testMove()
    {
        $list = &new Kolab_List();
        $list->_imap = &new Horde_Kolab_IMAP_test('', 0);

        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $folder->setList($list);
        $data->setFolder($folder);
        /** 
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->_cache->expire();

        $data2 = &new Kolab_Data('contact');
        $folder2 = &new Kolab_Folder('INBOX/NewContacts');
        $folder2->setList($list);
        $data2->setFolder($folder2);
        /** 
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data2->getObjectIds();
        $data2->_cache->expire();

        $result = $data->move('1', 'INBOX%20NewContacts');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertFalse($result);
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $result = $data->move('1', rawurlencode('INBOX/NewContacts'));
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $ids = $data->getObjectIds();
        if (is_a($ids, 'PEAR_Error')) {
            $this->assertEquals('', $ids->message);
        }
        $this->assertTrue(empty($ids));

        $data2->synchronize();
        $ids = $data2->getObjectIds();
        if (is_a($ids, 'PEAR_Error')) {
            $this->assertEquals('', $ids->message);
        }
        $this->assertEquals(1, count($ids));

        $result = $data2->delete('1');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
    }

    /**
     * Test saving data.
     */
    public function testSave()
    {
        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $data->setFolder($folder);
        /** 
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->_cache->expire();
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );

        $result = $data->save($object, '1000');
        $this->assertEquals("Old object 1000 does not exist.", $result->message);

        $result = $data->save($object);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $id = $data->_getStorageId('1');
        $this->assertTrue($data->_storageIdExists($id));
        $this->assertTrue($data->objectUidExists('1'));

        $object = $data->getObject('1');
        $this->assertEquals('Gunnar', $object['given-name']);

        $objects = $data->getObjects();
        if (is_a($objects, 'PEAR_Error')) {
            $this->assertEquals('', $objects->message);
        }
        $this->assertEquals(1, count($objects));

        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );

        $result = $data->save($object, '1');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $this->assertNotEquals($id, $data->_getStorageId('1'));
        $result = $data->delete('1');
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $this->assertFalse($data->_getStorageId('1'));
        $this->assertFalse($data->_storageIdExists($id));
        $this->assertFalse($data->objectUidExists('1'));

        $object = $data->getObject('1');
        $this->assertEquals("Kolab cache: Object uid 1 does not exist in the cache!", $object->message);

        $objects = $data->getObjects();
        if (is_a($objects, 'PEAR_Error')) {
            $this->assertEquals('', $objects->message);
        }
        $this->assertEquals(0, count($objects));
    }

    /**
     * Test clearing data in a folder.
     */
    public function testObjectDeleteAll()
    {
        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $data->setFolder($folder);
        /** 
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->_cache->expire();
        $result = $data->deleteAll();
        $this->assertTrue($result);

        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $result = $data->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $ids = $data->getObjectIds();
        if (is_a($ids, 'PEAR_Error')) {
            $this->assertEquals('', $ids->message);
        }
        $this->assertTrue(empty($ids));
    }

}
