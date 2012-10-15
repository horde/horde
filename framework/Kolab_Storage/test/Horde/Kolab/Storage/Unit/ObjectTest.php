<?php
/**
 * Tests the Kolab mime message parser.
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
require_once __DIR__ . '/../Autoload.php';

/**
 * Tests the Kolab mime message parser.
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
class Horde_Kolab_Storage_Unit_ObjectTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testInvalidInitialStructure()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $object->load('1', $this->folder, $data, new stdClass);
    }

    public function testObjectType()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $this->assertEquals('event', $object->getType());
    }

    public function testObjectTypeDeviatesFromFolderType()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $headers->expects($this->once())
            ->method('getValue')
            ->with('X-Kolab-Type')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.note')
        );
        $this->assertEquals('note', $object->getType());
    }

    public function testMissingKolabPart()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $headers->expects($this->once())
            ->method('getValue')
            ->with('X-Kolab-Type')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.task')
        );
        $this->assertEquals(
            Horde_Kolab_Storage_Object::TYPE_INVALID,
            $object->getType()
        );
        $this->assertContains(
            Horde_Kolab_Storage_Object::ERROR_MISSING_KOLAB_PART,
            array_keys($object->getParseErrors())
        );
        $this->assertTrue($object->hasParseErrors());
    }

    public function testObjectRetainsHeadersIfLoaded()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $headers->expects($this->once())
            ->method('getValue')
            ->with('X-Kolab-Type')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.note')
        );
        $this->assertSame($headers, $object->getHeaders());
    }

    public function testObjectFetchesHeadersOnRequest()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $this->assertSame($headers, $object->getHeaders());
    }

    public function testObjectRetainsContentIfLoaded()
    {
        $data_string = "<?xml version=\"1.0\"?>\n<kolab><test/></kolab>";
        $content = fopen('php://temp', 'r+');
        fwrite($content, $data_string);

        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));

        $this->driver->expects($this->once())
            ->method('fetchBodypart')
            ->with('INBOX/Calendar', '1', '2')
            ->will($this->returnValue($content));
        $object->setDriver($this->driver);

        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $this->assertSame($content, $object->getContent());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testGetDriverThrowsExceptionIfUnset()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->create(
            $this->getMock('Horde_Kolab_Storage_Folder'),
            $this->getMock('Horde_Kolab_Storage_Object_Writer'),
            'event'
        );
    }

    public function testSetContent()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setContent('A');
        $this->assertEquals('A', $object->getContent());
    }

    public function testSetData()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('a' => 'A'));
        $this->assertEquals('A', $object['a']);
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

    public function testOffsetPresent()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('a' => 'A'));
        $this->assertTrue(isset($object['a']));
    }

    public function testOffsetMissing()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('a' => 'A'));
        $this->assertFalse(isset($object['b']));
    }

    public function testOffsetWithoutData()
    {
        $object = new Horde_Kolab_Storage_Object();
        $this->assertFalse(isset($object['b']));
    }

    public function testOffsetGet()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('a' => 'A'));
        $this->assertEquals('A', $object['a']);
    }

    public function testOffsetSet()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object['a'] = 'A';
        $this->assertEquals(array('a' => 'A'), $object->getData());
    }

    public function testOffsetUnset()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('a' => 'A', 'b' => 'B'));
        unset($object['b']);
        unset($object['uid']);
        $this->assertEquals(array('a' => 'A'), $object->getData());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testUnserializeInvalidData()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->unserialize(serialize('A'));
    }        

    public function testSerializeUnserializeRetainsData()
    {
        $data = array('a' => 'a');
        $object = new Horde_Kolab_Storage_Object();
        $object->setData($data);
        $new_object = new Horde_Kolab_Storage_Object();
        $new_object->unserialize($object->serialize());
        $result = $new_object->getData();
        $this->assertEquals($data['a'], $result['a']);
    }        

    public function testSerializeUnserializeRetainsErrors()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $headers->expects($this->once())
            ->method('getValue')
            ->with('X-Kolab-Type')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.task')
        );
        $new_object = new Horde_Kolab_Storage_Object();
        $new_object->unserialize($object->serialize());
        $this->assertContains(
            Horde_Kolab_Storage_Object::ERROR_MISSING_KOLAB_PART,
            array_keys($new_object->getParseErrors())
        );
    }        

    public function testSerializeUnserializeRetainsType()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $new_object = new Horde_Kolab_Storage_Object();
        $new_object->unserialize($object->serialize());
        $this->assertEquals('event', $new_object->getType());
    }

    public function testSerializeUnserializeRetainsBackendIdAndFolder()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $headers = $this->getMock('Horde_Mime_Headers');
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $new_object = new Horde_Kolab_Storage_Object();
        $new_object->unserialize($object->serialize());
        $new_object->setDriver($this->driver);
        $this->assertSame($headers, $new_object->getHeaders());
    }

    public function testSerializeUnserializeForgetsContent()
    {
        $data_string = "<?xml version=\"1.0\"?>\n<kolab><test/></kolab>";
        $content = fopen('php://temp', 'r+');
        fwrite($content, $data_string);

        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));

        $this->driver->expects($this->exactly(2))
            ->method('fetchBodypart')
            ->with('INBOX/Calendar', '1', '2')
            ->will($this->returnValue($content));
        $object->setDriver($this->driver);

        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $new_object = new Horde_Kolab_Storage_Object();
        $new_object->unserialize($object->serialize());
        $new_object->setDriver($this->driver);
        $this->assertSame($content, $new_object->getContent());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testFetchingContentsFailsWithMissingFolder()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(null));
        $object->setDriver($this->driver);

        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testFetchingContentsFailsWithMissingBackendId()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);

        $object->load(
            null,
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testFetchingContentsFailsWithMissingMimeId()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $headers = $this->getMock('Horde_Mime_Headers');
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);
        $this->driver->expects($this->once())
            ->method('fetchHeaders')
            ->with('INBOX/Calendar', '1')
            ->will($this->returnValue($headers));

        $object->load(
            '1',
            $this->folder,
            $data,
            new Horde_Mime_Part()
        );
        $object->getContent();
    }

    public function testObjectLoadsData()
    {
        $data_string = "<?xml version=\"1.0\"?>\n<kolab><test/></kolab>";
        $content = fopen('php://temp', 'r+');
        fwrite($content, $data_string);

        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $data->expects($this->once())
            ->method('load');
        $headers = $this->getMock('Horde_Mime_Headers');
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));

        $this->driver->expects($this->once())
            ->method('fetchBodypart')
            ->with('INBOX/Calendar', '1', '2')
            ->will($this->returnValue($content));
        $object->setDriver($this->driver);

        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
    }

    public function testMimeEnvelope()
    {
        $driver = new Horde_Kolab_Storage_Stub_Driver('user');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX'));
        $writer = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $object->setDriver($driver);
        $object->create($folder, $writer, 'event');
        $this->assertContains('MIME-Version: 1.0', $driver->messages['INBOX'][0]);
    }

    public function testEnvelope()
    {
        setlocale(LC_MESSAGES, 'C');

        $driver = new Horde_Kolab_Storage_Stub_Driver('user');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX'));
        $writer = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('uid' => 'UID'));
        $object->setDriver($driver);
        $object->create($folder, $writer, 'event');

        $this->assertContains('Content-Disposition: attachment; filename="Kolab Groupware Data"', $driver->messages['INBOX'][0]);
        $this->assertContains('Content-Type: multipart/mixed;', $driver->messages['INBOX'][0]);
        $this->assertContains('Content-Type: text/plain; name="Kolab Groupware Information"; charset=utf-8', $driver->messages['INBOX'][0]);
        $this->assertContains('Content-Disposition: inline; filename="Kolab Groupware Information"', $driver->messages['INBOX'][0]);
        $this->assertContains(
            "This is a Kolab Groupware object. To view this object you will need an email\r
client that understands the Kolab Groupware format. For a list of such email\r
clients please visit http://www.kolab.org/content/kolab-clients",
            $driver->messages['INBOX'][0]
        );
        $this->assertContains('User-Agent: Horde_Kolab_Storage ' . Horde_Kolab_Storage::VERSION, $driver->messages['INBOX'][0]);
        $this->assertContains('Subject: UID', $driver->messages['INBOX'][0]);
        $this->assertContains('From: user', $driver->messages['INBOX'][0]);
        $this->assertContains('To: user', $driver->messages['INBOX'][0]);
        $this->assertContains('X-Kolab-Type: application/x-vnd.kolab.event', $driver->messages['INBOX'][0]);
        $this->assertContains('Content-Type: application/x-vnd.kolab.event; name=kolab.xml', $driver->messages['INBOX'][0]);
        $this->assertEquals(
            array(
                0 => 'multipart/mixed',
                1 => 'text/plain',
                2 => 'application/x-vnd.kolab.event'
            ),
            Horde_Mime_Part::parseMessage($driver->messages['INBOX'][0])->contentTypeMap(true)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testDriverException()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(false));
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX'));
        $writer = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('uid' => 'UID'));
        $object->setDriver($driver);
        $object->create($folder, $writer, 'event');
    }

    public function testGetUid()
    {
        $object = new Horde_Kolab_Storage_Object();
        $this->assertInternalType('string', $object->getUid());
    }

    public function testNewUid()
    {
        $object = new Horde_Kolab_Storage_Object();
        $uid = $object->getUid();
        $data = $object->getData();
        $this->assertEquals($uid, $data['uid']);
    }

    public function testPresetUid()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('uid' => 'UID'));
        $this->assertEquals('UID', $object->getUid());
    }

    public function testSave()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $this->driver->expects($this->once())
            ->method('fetchComplete')
            ->will(
                $this->returnValue(
                    array(
                        new Horde_Mime_Headers(),
                        $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
                    )
                )
            );
        $this->driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(true));
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );

        $object->save($data);
    }

    /**
     * @expectedException Horde_Kolab_Storage_Object_Exception
     */
    public function testSaveException()
    {
        $data = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $this->driver->expects($this->once())
            ->method('fetchComplete')
            ->will(
                $this->returnValue(
                    array(
                        new Horde_Mime_Headers(),
                        $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
                    )
                )
            );
        $this->driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(false));
        $object = new Horde_Kolab_Storage_Object();
        $this->folder->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('event'));
        $this->folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX/Calendar'));
        $object->setDriver($this->driver);
        $object->load(
            '1',
            $this->folder,
            $data,
            $this->getMultipartMimeMessage('application/x-vnd.kolab.event')
        );
        $object->save($data);
    }

    public function testKolabPart()
    {
        $driver = new Horde_Kolab_Storage_Stub_Driver('user');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('INBOX'));
        $writer = $this->getMock('Horde_Kolab_Storage_Object_Writer');
        $writer->expects($this->once())
            ->method('save')
            ->will($this->returnValue('<content/>'));
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array('uid' => 'UID'));
        $object->setDriver($driver);
        $object->create($folder, $writer, 'event');

        $message = $driver->fetchComplete('INBOX', 0);
        $this->assertEquals('inline', $message[1]->getPart('2')->getDisposition());
        $this->assertEquals(
            'xml',
            $message[1]->getPart('2')->getDispositionParameter('x-kolab-type')
        );
        $this->assertEquals('kolab.xml', $message[1]->getPart('2')->getName());
        $this->assertEquals(
            'application/x-vnd.kolab.event',
            $message[1]->getPart('2')->getType()
        );
        $this->assertEquals('<content/>', trim($message[1]->getPart('2')->getContents()));
    }

    public function testAutomaticUid()
    {
        $object = new Horde_Kolab_Storage_Object();
        $object->setData(array());
        $this->assertEquals(array('uid'), array_keys($object->getData()));
    }
}