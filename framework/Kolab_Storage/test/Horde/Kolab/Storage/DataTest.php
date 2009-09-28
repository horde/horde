<?php
/**
 * Test the Kolab data handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once 'Autoload.php';

/**
 * Test the Kolab data handler.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_DataTest extends Horde_Kolab_Storage_Scenario
{

    /**
     * Test setup.
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        $this->storage = $this->authenticate($world['auth'],
                         'wrobel@example.org',
                         'none');

        $this->folder = $this->prepareNewFolder($this->storage, 'Contacts', 'contact', true);
        $this->prepareNewFolder($this->storage, 'NewContacts', 'contact');
    }

    /**
     * Test destruction.
     */
    public function tearDown()
    {
        Horde_Imap_Client_Mock::clean();
        $this->storage->clean();
    }

    /**
     * Test cache access.
     */
    public function testGetCacheKey()
    {
        $data = new Horde_Kolab_Storage_Data('test');

        $folder = new Horde_Kolab_Storage_Folder('INBOX/Test');
        $data->setFolder($folder);
        $this->assertEquals('user/wrobel/Test', $data->getCacheKey());
    }

    /**
     * Test object deletion.
     */
    public function testDelete()
    {
        $data = new Horde_Kolab_Storage_Data('contact');
        $data->setFolder($this->folder);

        /**
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->expireCache();

        $result = $data->delete('1');
        $this->assertFalse($result);
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        $this->assertTrue($result);
        $ids = $data->getObjectIds();
        foreach ($ids as $id) {
            $uid = $data->getStorageId($id);
            $result = $data->delete($uid);
            $this->assertTrue($result);
        }
        $ids = $data->getObjectIds();
        $this->assertTrue(empty($ids));
    }

    /**
     * Test object moving.
     */
    public function testMove()
    {
        $data = new Horde_Kolab_Storage_Data('contact');
        $folder = $this->storage->getFolder('INBOX/Contacts');
        $data->setFolder($folder);
        /**
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->expireCache();

        $data2 = new Horde_Kolab_Storage_Data('contact');
        $folder2 = $this->storage->getFolder('INBOX/NewContacts');
        $data2->setFolder($folder2);
        /**
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data2->getObjectIds();
        $data2->expireCache();

        $result = $data->move('1', 'INBOX%20NewContacts');
        $this->assertFalse($result);
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        $this->assertTrue($result);

        $result = $data->move('1', rawurlencode('INBOX/NewContacts'));
        $this->assertTrue($result);

        $ids = $data->getObjectIds();
        $this->assertTrue(empty($ids));

        $data2->synchronize();
        $ids = $data2->getObjectIds();
        $this->assertEquals(1, count($ids));

        $result = $data2->delete('1');
        $this->assertTrue($result);
    }

    /**
     * Test saving data.
     */
    public function testSave()
    {
        $data = new Horde_Kolab_Storage_Data('contact');
        $data->setFolder($this->folder);
        /**
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->expireCache();
        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );

        try {
            $result = $data->save($object, '1000');
        } catch (Exception $e) {
            $this->assertEquals("Old object 1000 does not exist.", $e->getMessage());
        }
        $result = $data->save($object);
        $this->assertTrue($result);

        $id = $data->getStorageId('1');
        $this->assertTrue($data->storageIdExists($id));
        $this->assertTrue($data->objectUidExists('1'));

        $object = $data->getObject('1');
        $this->assertEquals('Gunnar', $object['given-name']);

        $objects = $data->getObjects();
        $this->assertEquals(1, count($objects));

        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );

        $result = $data->save($object, '1');
        $this->assertTrue($result);
        $this->assertNotEquals($id, $data->getStorageId('1'));
        $result = $data->delete('1');
        $this->assertTrue($result);
        $this->assertFalse($data->getStorageId('1'));
        $this->assertFalse($data->storageIdExists($id));
        $this->assertFalse($data->objectUidExists('1'));

        try {
        $object = $data->getObject('1');
        } catch (Exception $e) {
            $this->assertEquals("Kolab cache: Object uid 1 does not exist in the cache!", $e->getMessage());
        }

        $objects = $data->getObjects();
        $this->assertEquals(0, count($objects));
    }

    /**
     * Test clearing data in a folder.
     */
    public function testObjectDeleteAll()
    {
        $data = new Horde_Kolab_Storage_Data('contact');
        $data->setFolder($this->folder);
        /**
         * During testing we want to ensure that we do not access any
         * old, cached data. The cache gets loaded when calling
         * getObjectIds and is manually expired afterwards.
         */
        $result = $data->getObjectIds();
        $data->expireCache();
        $result = $data->deleteAll();
        $this->assertTrue($result);

        $object = array(
            'uid' => '1',
            'given-name' => 'Gunnar',
            'full-name' => 'Gunnar Wrobel',
            'email' => 'p@rdus.de'
        );
        $result = $data->save($object);
        $this->assertTrue($result);

        $result = $data->deleteAll();
        $this->assertTrue($result);

        $ids = $data->getObjectIds();
        $this->assertTrue(empty($ids));
    }

}
