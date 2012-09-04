<?php
/**
 * Test the MimeType representations.
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
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the MimeType representations.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Object_MimeTypeTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getType
     */
    public function testGetMimeType($type)
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_MimeType();
        $this->assertEquals(
            'application/x-vnd.kolab.' . $type,
            $mimeType->getType($type)->getMimeType()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUndefinedMimeType()
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_MimeType();
        $mimeType->getType('UNDEFINED')->getMimeType();
    }

    /**
     * @dataProvider getType
     */
    public function testMatchMimeType($type)
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_MimeType();
        $this->assertEquals(
            2,
            $mimeType->getType($type)->matchMimeId(
                array(
                    'multipart/mixed',
                    'foo/bar',
                    'application/x-vnd.kolab.' . $type
                )
            )
        );
    }

    public function getType()
    {
        return array(
            array('contact'),
            array('event'),
            array('note'),
            array('task'),
            array('h-prefs'),
            array('h-ledger'),
        );
    }
}
