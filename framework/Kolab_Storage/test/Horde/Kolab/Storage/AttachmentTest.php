<?php
/**
 * Test the handling of attachments.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/AttachmentTest.php,v 1.4 2009/06/09 23:23:39 slusarz Exp $
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
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/Data.php';
require_once 'Horde/Kolab/IMAP.php';
require_once 'Horde/Kolab/IMAP/test.php';

/**
 * Test the handling of attachments.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/AttachmentTest.php,v 1.4 2009/06/09 23:23:39 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_AttachmentTest extends Horde_Kolab_Test_Storage
{

    /**
     * Test setup.
     *
     * @return NULL
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $this->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);
    }

    /**
     * Test storing attachments.
     *
     * @return NULL
     */
    public function testCacheAttachmentInFile()
    {
        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $data->setFolder($folder);

        $atc1 = Horde_Util::getTempFile();
        $fh = fopen($atc1, 'w');
        fwrite($fh, 'test');
        fclose($fh);

        $object = array('uid' => '1',
                        'full-name' => 'User Name',
                        'email' => 'user@example.org',
                        'inline-attachment' => array('test.txt'),
                        '_attachments' => array('test.txt'=> array('type' => 'text/plain',
                                                                   'path' => $atc1,
                                                                   'name' => 'test.txt')));

        $result = $data->save($object);
        $this->assertNoError($result);
        $result = $data->getObject(1);
        $this->assertNoError($result);
        $this->assertTrue(isset($result['_attachments']['test.txt']));
        $this->assertEquals("test\n", $data->getAttachment($result['_attachments']['test.txt']['key']));
    }

    /**
     * Test storing attachments.
     *
     * @return NULL
     */
    public function testCacheAttachmentAsContent()
    {
        $data = &new Kolab_Data('contact');
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $data->setFolder($folder);

        $object = array('uid' => '1',
                        'full-name' => 'User Name',
                        'email' => 'user@example.org',
                        'inline-attachment' => array('test.txt'),
                        '_attachments' => array('test.txt'=> array('type' => 'text/plain',
                                                                   'content' => 'test',
                                                                   'name' => 'test.txt')));

        $result = $data->save($object);
        $this->assertNoError($result);
        $result = $data->getObject(1);
        $this->assertNoError($result);
        $this->assertTrue(isset($result['_attachments']['test.txt']));
        $this->assertEquals("test\n", $data->getAttachment($result['_attachments']['test.txt']['key']));
    }
}
