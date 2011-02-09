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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the Xml parser.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_ParserTest
extends PHPUnit_Framework_TestCase
{
    public function testParseString()
    {
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $parser->parse("<?xml version=\"1.0\"?>\n<kolab><test/></kolab>");
    }

    public function testParseResource()
    {
        $data = fopen('php://temp', 'r+');
        fwrite($data, "<?xml version=\"1.0\"?>\n<kolab><test/></kolab>");
        $parser = new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
        $parser->parse($data);
    }
}
