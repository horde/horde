<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml_Element
 * @subpackage UnitTests
 */

require_once __DIR__ . '/Autoload.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml_Element
 * @subpackage UnitTests
 */
class Horde_Xml_Element_ElementTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->element = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $this->namespacedElement = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));
    }

    public function testXml()
    {
        $el = new Horde_Xml_Element('<root href="#">Root</root>');
        $this->assertEquals('Root', (string)$el);
        $this->assertEquals('#', $el['href']);
    }

    public function testInvalidXml()
    {
        $failed = false;
        try {
            new Horde_Xml_Element('<root');
        } catch (Horde_Xml_Element_Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed, 'Invalid XML should result in an exception');
    }

    public function testSerialization()
    {
        $elt = new Horde_Xml_Element('<entry><title>Test</title></entry>');
        $this->assertEquals('Test', $elt->title(), 'Value before serialization/unserialization');
        $serialized = serialize($elt);
        $unserialized = unserialize($serialized);
        $this->assertEquals('Test', $unserialized->title(), 'Value after serialization/unserialization');
    }

    public function testArrayGet()
    {
        $this->assertTrue(is_array($this->element->entry));
        $this->assertTrue(is_array($this->namespacedElement->entry));

        foreach ($this->element->entry as $entry) {
            $this->assertTrue($entry instanceof Horde_Xml_Element);
        }

        foreach ($this->namespacedElement->entry as $entry) {
            $this->assertTrue($entry instanceof Horde_Xml_Element);
        }
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->element[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->element['version']), 'Version should be set');

        $this->assertFalse(isset($this->namespacedElement[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->namespacedElement['version']), 'Version should be set');
    }

    public function testOffsetGet()
    {
        $this->assertEquals('1.0', $this->element['version'], 'Version should be 1.0');
        $this->assertEquals('1.0', $this->namespacedElement['version'], 'Version should be 1.0');
    }

    public function testOffsetSet()
    {
        $this->element['category'] = 'tests';
        $this->assertTrue(isset($this->element['category']), 'Category should be set');
        $this->assertEquals('tests', $this->element['category'], 'Category should be tests');

        $this->namespacedElement['atom:category'] = 'tests';
        $this->assertTrue(isset($this->namespacedElement['atom:category']), 'Namespaced category should be set');
        $this->assertEquals('tests', $this->namespacedElement['atom:category'], 'Namespaced category should be tests');

        $this->namespacedElement['xmldata:dt'] = 'dateTime.rfc1123';

        // Changing an existing index.
        $oldEntry = $this->element['version'];
        $this->element['version'] = '1.1';
        $this->assertTrue($oldEntry != $this->element['version'], 'Version should have changed');
    }

    public function testOffsetUnset()
    {
        $element = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $namespacedElement = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));

        $this->assertTrue(isset($element['version']));
        unset($element['version']);
        $this->assertFalse(isset($element['version']), 'Version should be unset');
        $this->assertEquals('', $element['version'], 'Version should be equal to the empty string');

        $this->assertTrue(isset($namespacedElement['version']));
        unset($namespacedElement['version']);
        $this->assertFalse(isset($namespacedElement['version']), 'Version should be unset');
        $this->assertEquals('', $namespacedElement['version'], 'Version should be equal to the empty string');

        $namespacedElement['atom:category'] = 'tests';
        $this->assertTrue(isset($namespacedElement['atom:category']), 'Namespaced Category should be set');
        unset($namespacedElement['atom:category']);
        $this->assertFalse(isset($namespacedElement['atom:category']), 'Category should be unset');
        $this->assertEquals('', $namespacedElement['atom:category'], 'Category should be equal to the empty string');
    }

    public function testGet()
    {
        $this->assertEquals('Atom Example', (string)$this->element->title);
        $this->assertEquals('Atom Example', (string)$this->namespacedElement->title);
        $this->assertEquals('4', (string)$this->element->totalResults);
        $this->assertEquals('4', (string)$this->namespacedElement->totalResults);
    }

    public function testSet()
    {
        $this->element->category = 'tests';
        $this->assertTrue(isset($this->element->category), 'Category should be set');
        $this->assertEquals('tests', (string)$this->element->category, 'Category should be tests');
    }

    public function testUnset()
    {
        $element = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $namespacedElement = new Horde_Xml_Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));

        $this->assertTrue(isset($element->title));
        unset($element->title);
        $this->assertFalse(isset($element->title));

        $this->assertTrue(isset($namespacedElement->title));
        unset($namespacedElement->title);
        $this->assertFalse(isset($namespacedElement->title));
    }

    public function testIsInitialized()
    {
        $e = new Horde_Xml_Element('<element />');

        $e->author->name['last'] = 'Lastname';
        $e->author->name['first'] = 'Firstname';
        $e->author->name->{'Firstname:url'} = 'www.example.com';

        $e->author->title['foo'] = 'bar';
        if ($e->pants()) {
            $this->fail('<pants> does not exist, it should not have a true value');
            // This should not create an element in the actual tree.
        }
        if ($e->pants()) {
            $this->fail('<pants> should not have been created by testing for it');
            // This should not create an element in the actual tree.
        }

        $xml = $e->saveXML();

        $this->assertFalse(strpos($xml, 'pants'), '<pants> should not be in the xml output');
        $this->assertTrue(strpos($xml, 'www.example.com') !== false, 'the url attribute should be set');
    }

    public function testStrings()
    {
        $xml = "<entry>
    <title> Using C++ Intrinsic Functions for Pipelined Text Processing</title>
    <id>http://www.oreillynet.com/pub/wlg/8356</id>
    <link rel='alternate' href='http://www.oreillynet.com/pub/wlg/8356'/>
    <summary type='xhtml'>
    <div xmlns='http://www.w3.org/1999/xhtml'>
    A good C++ programming technique that has almost no published material available on the WWW relates to using the special pipeline instructions in modern CPUs for faster text processing. Here's example code using C++ intrinsic functions to give a fourfold speed increase for a UTF-8 to UTF-16 converter compared to the original C/C++ code.
    </div>
    </summary>
    <author><name>Rick Jelliffe</name></author>
    <updated>2005-11-07T08:15:57-08:00</updated>
</entry>";
        $element = new Horde_Xml_Element($xml);

        $this->assertTrue($element->summary instanceof Horde_Xml_Element, '__get access should return a Horde_Xml_Element instance');
        $this->assertFalse($element->summary() instanceof Horde_Xml_Element, 'method access should not return a Horde_Xml_Element instance');
        $this->assertTrue(is_string($element->summary()), 'method access should return a string');
        $this->assertFalse(is_string($element->summary), '__get access should not return a string');
    }

    public function testAppendChild()
    {
        $e = new Horde_Xml_Element('<element />');
        $e2 = new Horde_Xml_Element('<child />');

        $e->appendChild($e2);
        $this->assertEquals('<element><child/></element>', $e->saveXmlFragment());
    }

    public function testFromArray()
    {
        $e = new Horde_Xml_Element('<element />');

        $e->fromArray(array('user' => 'Joe Schmoe',
                            'atom:title' => 'Test Title',
                            'child#href' => 'http://www.example.com/',
                            '#title' => 'Test Element'));

        $this->assertEquals('<element title="Test Element"><user>Joe Schmoe</user><atom:title xmlns:atom="http://www.w3.org/2005/Atom">Test Title</atom:title><child href="http://www.example.com/"/></element>',
                            $e->saveXmlFragment());

        $e = new Horde_Xml_Element('<element />');
        $e->fromArray(array('author' => array('name' => 'Joe', 'email' => 'joe@example.com')));

        $this->assertEquals('<element><author><name>Joe</name><email>joe@example.com</email></author></element>',
                            $e->saveXmlFragment());
    }

    public function testIllegalFromArray()
    {
        $failed = false;
        $e = new Horde_Xml_Element('<element />');
        try {
            $e->fromArray(array('#name' => array('foo' => 'bar')));
        } catch (InvalidArgumentException $e) {
            $failed = true;
        }
        $this->assertTrue($failed);
    }

    public function testCustomGetterGet()
    {
        $xml = '<element><author><name>Joe</name><email>joe@example.com</email></author></element>';
        $e = new Horde_Xml_Element_CustomGetter($xml);

        $this->assertEquals($e->author, $e->writer);
        $this->assertEquals($e->author->name, $e->psuedonym);
    }

    public function testCustomGetterCall()
    {
        $xml = '<element><author><name>Joe</name><email>joe@example.com</email></author></element>';
        $e = new Horde_Xml_Element_CustomGetter($xml);

        $this->assertEquals($e->author(), $e->writer());
        $this->assertEquals($e->author->name(), $e->psuedonym());
    }

}

class Horde_Xml_Element_CustomGetter extends Horde_Xml_Element
{
    public function getWriter()
    {
        return $this->author;
    }

    public function getPsuedonym()
    {
        return $this->author->name;
    }

}
