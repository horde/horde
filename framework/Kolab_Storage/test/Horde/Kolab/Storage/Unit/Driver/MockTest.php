<?php
/**
 * Test the Kolab mock driver.
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
 * Test the Kolab mock driver.
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
class Horde_Kolab_Storage_Unit_Driver_MockTest
extends Horde_Kolab_Storage_TestCase
{
    public function testGetMailboxesReturnsArray()
    {
        $this->assertType('array', $this->getNullMock()->getMailboxes());
    }

    public function testGetMailboxesEmpty()
    {
        $this->assertEquals(array(), $this->getEmptyMock()->getMailboxes());
    }

    public function testGetMailboxesReturnsMailboxes()
    {
        $this->assertEquals(
            array('INBOX', 'INBOX/a'),
            $this->getTwoFolderMock()->getMailboxes()
        );
    }

    public function testPersonalFolder()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $this->assertEquals(
            'lrswipkxtecda',
            $mock->getMyAcl('INBOX/Test')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetAclFailsOnMissing()
    {
        $this->getNullMock()->getAcl('INBOX/test');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetAclOnHidden()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', $mock->getAuth(), '');
        $mock->getAcl('INBOX/Test');
    }

    public function testGetAclOnNoAdmin()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', $mock->getAuth(), 'lr');
        try {
            $mock->getAcl('INBOX/Test');
        } catch (Horde_Kolab_Storage_Exception $e) {
            $this->assertEquals('Permission denied!', $e->getMessage());
        }
    }

    public function testGetAclWithAnyone()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anyone', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $this->assertEquals(array('anyone' => 'a'), $mock->getAcl('INBOX/Test'));
    }

    public function testGetAclWithAnonymous()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anonymous', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $this->assertEquals(array('anonymous' => 'a'), $mock->getAcl('INBOX/Test'));
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMyAclFailsOnMissing()
    {
        $this->getNullMock()->getMyAcl('INBOX/test');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testGetMyAclOnHidden()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->getMyAcl('INBOX/Test');
    }

    public function testGetMyAclWithAnyone()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anyone', 'l');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $this->assertEquals('l', $mock->getMyAcl('INBOX/Test'));
    }

    public function testGetMyAclWithAnonymous()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anonymous', 'l');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $this->assertEquals('l', $mock->getMyAcl('INBOX/Test'));
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testSetAclFailsOnMissing()
    {
        $this->getNullMock()->setAcl('INBOX/test', 'a', 'b');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testSetAclOnHidden()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->setAcl('INBOX/Test', 'a', 'b');
    }

    public function testSetAclOnNoAdmin()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', $mock->getAuth(), 'lr');
        try {
            $mock->setAcl('INBOX/Test', 'a', 'b');
        } catch (Horde_Kolab_Storage_Exception $e) {
            $this->assertEquals('Permission denied!', $e->getMessage());
        }
    }

    public function testSetAclWithAnyone()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anyone', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->setAcl('INBOX/Test', 'a', 'b');
    }

    public function testSetAclWithAnonymous()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anonymous', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->setAcl('INBOX/Test', 'a', 'b');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testDeleteAclFailsOnMissing()
    {
        $this->getNullMock()->deleteAcl('INBOX/test', 'a');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testDeleteAclOnHidden()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', $mock->getAuth(), '');
        $mock->deleteAcl('INBOX/Test', 'a');
    }

    public function testDeleteAclOnNoAdmin()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', $mock->getAuth(), 'lr');
        try {
            $mock->deleteAcl('INBOX/Test', 'a');
        } catch (Horde_Kolab_Storage_Exception $e) {
            $this->assertEquals('Permission denied!', $e->getMessage());
        }
    }

    public function testDeleteAclWithAnyone()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anyone', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->deleteAcl('INBOX/Test', 'anyone');
    }

    public function testDeleteAclWithAnonymous()
    {
        $mock = $this->getNullMock();
        $mock->create('INBOX/Test');
        $mock->setAcl('INBOX/Test', 'anonymous', 'a');
        $mock->deleteAcl('INBOX/Test', $mock->getAuth());
        $mock->deleteAcl('INBOX/Test', 'anonymous');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testSetAnnotationFailsOnMissing()
    {
        $this->getNullMock()->setAnnotation('INBOX/test', 'a', 'b');
    }

    public function testListAnnotationReturnsArray()
    {
        $this->assertType(
            'array',
            $this->getNullMock()->listAnnotation(
                '/shared/vendor/kolab/folder-type'
            )
        );
    }

    public function testListAnnotationSize()
    {
        $this->assertEquals(
            4,
            count(
                $this->getAnnotatedMock()->listAnnotation(
                    '/shared/vendor/kolab/folder-type'
                )
            )
        );
    }

    public function testListAnnotationKeys()
    {
        $this->assertEquals(
            array('INBOX/Calendar', 'INBOX/Contacts', 'INBOX/Notes', 'INBOX/Tasks'),
            array_keys(
                $this->getAnnotatedMock()->listAnnotation(
                    '/shared/vendor/kolab/folder-type'
                )
            )
        );
    }

    public function testListAnnotationGermanKeys()
    {
        $this->assertEquals(
            array('INBOX/Kalender', 'INBOX/Kontakte', 'INBOX/Notizen', 'INBOX/Aufgaben'),
            array_keys(
                $this->getGermanAnnotatedMock()->listAnnotation(
                    '/shared/vendor/kolab/folder-type'
                )
            )
        );
    }

    public function testGetAnnotationReturnsAnnotationValue()
    {
        $this->markTestIncomplete();

        $data = array();
        $data['INBOX/Contacts']['annotations']['/vendor/kolab/folder-type']['value.shared'] = 'contact.default';
        $driver = new Horde_Kolab_Storage_Driver_Mock(
            $data
        );
        $this->assertEquals(
            'contact.default',
            $driver->getAnnotation('/vendor/kolab/folder-type', 'value.shared', 'INBOX/Contacts')
        );
    }

    public function testGetNamespaceReturnsNamespaceHandler()
    {
        $driver = new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(), array()
        );
        $this->assertType(
            'Horde_Kolab_Storage_Folder_Namespace',
            $driver->getNamespace()
        );
    }

    public function testGetNamespaceReturnsExpectedNamespaces()
    {
        $driver = new Horde_Kolab_Storage_Driver_Mock(
            new Horde_Kolab_Storage_Factory(), array('username' => 'test')
        );
        $namespaces = array();
        foreach ($driver->getNamespace() as $namespace) {
            $namespaces[$namespace->getName()] = array(
                'type' => $namespace->getType(),
                'delimiter' => $namespace->getDelimiter(),
            );
        }
        $this->assertEquals(
            array(
                'INBOX' => array(
                    'type' => 'personal',
                    'delimiter' => '/',
                ),
                'user' => array(
                    'type' => 'other',
                    'delimiter' => '/',
                ),
                '' => array(
                    'type' => 'shared',
                    'delimiter' => '/',
                ),
            ),
            $namespaces
        );
    }

    public function testGetIdReturnsString()
    {
        $this->assertType('string', $this->getNullMock()->getId());
    }
}
