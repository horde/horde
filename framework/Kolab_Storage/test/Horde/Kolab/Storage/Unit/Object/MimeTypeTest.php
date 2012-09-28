<?php
/**
 * Tests the Kolab mime type handling.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Tests the Kolab mime type handling.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Object_MimeTypeTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getObjectAndMimeTypes
     */
    public function testMimeTypeFromObjectType($type, $mime_type)
    {
        $this->assertEquals(
            $mime_type,
            Horde_Kolab_Storage_Object_MimeType::getMimeTypeFromObjectType($type)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUndefinedMimeObjectType()
    {
        Horde_Kolab_Storage_Object_MimeType::getMimeTypeFromObjectType('UNDEFINED');
    }

    /**
     * @dataProvider getObjectAndMimeTypes
     */
    public function testMatchMimePartToObjectType($type, $mime_type)
    {
        $this->assertEquals(
            2,
            Horde_Kolab_Storage_Object_MimeType::matchMimePartToObjectType(
                $this->getMultipartMimeMessage($mime_type), $type
            )
        );
    }

    public function testNoMatchToObjectType()
    {
        $this->assertFalse(
            Horde_Kolab_Storage_Object_MimeType::matchMimePartToObjectType(
                $this->getMultipartMimeMessage('dummy/dummy'), 'event'
            )
        );
    }

    /**
     * @dataProvider getObjectAndMimeTypes
     */
    public function testGetObjectTypeFromMimePart($type, $mime_type)
    {
        $this->assertEquals(
            $type,
            Horde_Kolab_Storage_Object_MimeType::getObjectTypeFromMimePart(
                $this->getMultipartMimeMessage($mime_type), 2
            )
        );
    }

    public function testGetUnknownObjectType()
    {
        $this->assertFalse(
            Horde_Kolab_Storage_Object_MimeType::getObjectTypeFromMimePart(
                $this->getMultipartMimeMessage('dummy/dummy'), 2
            )
        );
    }

    /**
     * @dataProvider getObjectAndMimeTypes
     */
    public function testMatchMimePartToHeaderType($type, $mime_type)
    {
        $headers = new Horde_Mime_Headers();
        $headers->addHeader('X-Kolab-Type', $mime_type);
        $this->assertEquals(
            array(2, $type),
            Horde_Kolab_Storage_Object_MimeType::matchMimePartToHeaderType(
                $this->getMultipartMimeMessage($mime_type), $headers
            )
        );
    }

    public function testUndefinedKolabTypeHeader()
    {
        $headers = new Horde_Mime_Headers();
        $this->assertFalse(
            Horde_Kolab_Storage_Object_MimeType::matchMimePartToHeaderType(
                $this->getMultipartMimeMessage('test/test'), $headers
            )
        );
    }

    private function getMultipartMimeMessage($mime_type)
    {
        $envelope = new Horde_Mime_Part();
        $envelope->setType('multipart/mixed');
        $foo = new Horde_Mime_Part();
        $foo->setType('foo/bar');
        $envelope->addPart($foo);
        $kolab = new Horde_Mime_Part();
        $kolab->setType($mime_type);
        $envelope->addPart($kolab);
        $envelope->buildMimeIds();
        return $envelope;
    }

    public function getObjectandMimeTypes()
    {
        return array(
            array('contact', 'application/x-vnd.kolab.contact'),
            array('distribution-list', 'application/x-vnd.kolab.contact.distlist'),
            array('event', 'application/x-vnd.kolab.event'),
            array('journal', 'application/x-vnd.kolab.journal'),
            array('note', 'application/x-vnd.kolab.note'),
            array('task', 'application/x-vnd.kolab.task'),
            array('configuration', 'application/x-vnd.kolab.configuration'),
            array('h-prefs', 'application/x-vnd.kolab.h-prefs'),
            array('h-ledger', 'application/x-vnd.kolab.h-ledger'),
        );
    }

    /**
     * @dataProvider getFolderAndMimeTypes
     */
    public function testMimeTypeFromFolderType($type, $mime_types)
    {
        $this->assertEquals(
            $mime_types,
            Horde_Kolab_Storage_Object_MimeType::getMimeTypesFromFolderType($type)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUndefinedMimeFolderType()
    {
        Horde_Kolab_Storage_Object_MimeType::getMimeTypesFromFolderType('UNDEFINED');
    }

    /**
     * @dataProvider getFolderAndMimeTypes
     */
    public function testMatchMimePartToFolderType($type, $mime_types)
    {
        foreach ($mime_types as $mime_type) {
            $this->assertEquals(
                array(2, Horde_Kolab_Storage_Object_MimeType::getObjectTypeFromMimeType($mime_type)),
                Horde_Kolab_Storage_Object_MimeType::matchMimePartToFolderType(
                    $this->getMultipartMimeMessage($mime_type), $type
                )
            );
        }
    }

    public function testNoMatchMimePartToFolderType()
    {
        $this->assertFalse(
            Horde_Kolab_Storage_Object_MimeType::matchMimePartToFolderType(
                $this->getMultipartMimeMessage('application/x-vnd.kolab.event'),
                'note'
            )
        );
    }


    public function getFolderandMimeTypes()
    {
        return array(
            array('contact',
                  array(
                      'application/x-vnd.kolab.contact',
                      'application/x-vnd.kolab.contact.distlist'
                  )
            ),
            array('event', array('application/x-vnd.kolab.event')),
            array('journal', array('application/x-vnd.kolab.journal')),
            array('note', array('application/x-vnd.kolab.note')),
            array('task', array('application/x-vnd.kolab.task')),
            array('h-prefs', array('application/x-vnd.kolab.h-prefs')),
            array('h-ledger', array('application/x-vnd.kolab.h-ledger')),
        );
    }

}