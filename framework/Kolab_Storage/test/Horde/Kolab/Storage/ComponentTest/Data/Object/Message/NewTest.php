<?php
/**
 * Tests the creation of new Kolab mime messages.
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
 * Tests the creation of new Kolab mime messages.
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
class Horde_Kolab_Storage_ComponentTest_Data_Object_Message_NewTest
extends PHPUnit_Framework_TestCase
{
    public function testStore()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $writer = new Horde_Kolab_Storage_Object_Writer_Format(
            $factory
        );

        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX'));

        $driver = new Horde_Kolab_Storage_Stub_Driver('user');
        $object = new Horde_Kolab_Storage_Object();
        $object->setDriver($driver);

        $object->setData(
            array('summary' => 'TEST', 'description' => 'test', 'uid' => 'ABC1234')
        );
        $object->create($folder, $writer, 'note');

        $result = $driver->messages['INBOX'][0];
        $result = preg_replace('/Date: .*/', 'Date: ', $result);
        $result = preg_replace('/boundary=".*"/', 'boundary=""', $result);
        $result = preg_replace('/--=_.*/', '--=_', $result);
        $result = preg_replace('/<creation-date>[^<]*/', '<creation-date>', $result);
        $result = preg_replace('/<last-modification-date>[^<]*/', '<last-modification-date>', $result);
        $result = preg_replace('/\r\n/', "\n", $result);
        $this->assertEquals(
            'From: user
To: user
Date: 
Subject: ABC1234
User-Agent: Horde_Kolab_Storage @version@
MIME-Version: 1.0
X-Kolab-Type: application/x-vnd.kolab.note
Content-Type: multipart/mixed; name="Kolab Groupware Data";
 boundary=""
Content-Disposition: attachment; filename="Kolab Groupware Data"

This message is in MIME format.

--=_
Content-Type: text/plain; name="Kolab Groupware Information"; charset=utf-8
Content-Disposition: inline; filename="Kolab Groupware Information"

This is a Kolab Groupware object. To view this object you will need an email
client that understands the Kolab Groupware format. For a list of such email
clients please visit http://www.kolab.org/content/kolab-clients
--=_
Content-Type: application/x-vnd.kolab.note; name=kolab.xml
Content-Disposition: inline; x-kolab-type=xml; filename=kolab.xml
Content-Transfer-Encoding: quoted-printable

<?xml version=3D"1.0" encoding=3D"UTF-8"?>
<note version=3D"1.0">
  <uid>ABC1234</uid>
  <body></body>
  <categories></categories>
  <creation-date></creation-date>
  <last-modification-date></last-modification-date>
  <sensitivity>public</sensitivity>
  <product-id>Horde_Kolab_Format_Xml-@version@ (api version: 2)</product-id=
>
  <summary>TEST</summary>
  <background-color>#000000</background-color>
  <foreground-color>#ffff00</foreground-color>
</note>

--=_
',
            $result
        );
    }
}