<?php
/**
 * Test the Xml parser.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the Xml parser.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_ParserTest
extends PHPUnit_Framework_TestCase
{
    public function testParseString()
    {
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $this->assertInstanceOf(
            'DOMDocument',
            $parser->parse("<?xml version=\"1.0\"?>\n<kolab><test/></kolab>")
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testParseMissingChild()
    {
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $parser->parse("<?xml version=\"1.0\"?>\n<kolab></kolab>");
    }

    public function testParseResource()
    {
        $data = fopen('php://temp', 'r+');
        fwrite($data, "<?xml version=\"1.0\"?>\n<kolab><test/></kolab>");
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $this->assertInstanceOf('DOMDocument', $parser->parse($data));
    }

    public function testParseUmlaut()
    {
        $data = fopen('php://temp', 'r+');
        fwrite($data, "<?xml version=\"1.0\"?>\n<kolab><ä/></kolab>");
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $this->assertInstanceOf('DOMDocument', $parser->parse($data));
    }

    public function testParseUmlautWithUtf8Encoding()
    {
        $data = fopen('php://temp', 'r+');
        fwrite($data, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<kolab><ä/></kolab>");
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $this->assertInstanceOf('DOMDocument', $parser->parse($data));
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testParseUmlautWrongEncoding()
    {
        $data = fopen('php://temp', 'r+');
        fwrite($data, "<?xml version=\"1.0\" encoding=\"windows-1252\"?>\n<kolab><ä/></kolab>");
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $parser->parse($data);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_ParseError
     */
    public function testSecondParseAttemptBroken()
    {
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $parser->parse("<?xml version=\"1.0\"?>\n<kolab><test/></kolab>");
        $parser->parse('
<?xml version="1.0"?>
<note version="1.0">
  <uid>4de4f1ed-b920-4af4-bdc7-5848576a7caa</uid>
  <body>^S</body>
  <categories></categories>
  <creation-date>2011-05-31T13:49:33Z</creation-date>
  <last-modification-date>2011-05-31T13:49:33Z</last-modification-date>
  <sensitivity>public</sensitivity>
  <product-id>Horde::Kolab</product-id>
  <summary>Horde continuous integration got updated</summary>
  <background-color>#000000</background-color>
  <foreground-color>#ffff00</foreground-color>
</note>
');
    }

    public function testParseEmpty()
    {
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $this->assertInstanceOf(
            'DOMDocument',
            $parser->parse('', array('relaxed' => true))
        );
    }

}
