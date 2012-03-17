<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Xml_Wbxml
 * @subpackage UnitTests
 */

class Horde_Xml_Wbxml_DecodeTest extends PHPUnit_Framework_TestCase
{
    public function testDecode()
    {
        if (!is_executable('/usr/bin/wbxml2xml')) {
            $this->markTestSkipped('/usr/bin/wbxml2xml is required for comparison tests.');
        }

        $decoder = new Horde_Xml_Wbxml_Decoder();

        foreach (glob(__DIR__ . '/../../../../doc/Horde/Xml/Wbxml/examples/*.wbxml') as $file) {
            $xml_ref = shell_exec('/usr/bin/wbxml2xml' . ' -m 0 -o - "' . $file . '" 2>/dev/null');
            $xml_ref = preg_replace(
                array(
                    // Ignore <?xml and <!DOCTYPE stuff:
                    '/<\?xml version=\"1\.0\"\?><!DOCTYPE [^>]*>/',
                    // Normalize empty tags.
                    '|<([^>]+)/>|'),
                array('', '<$1></$1>'),
                $xml_ref);

            $xml = $decoder->decodeToString(file_get_contents($file));

            if (is_string($xml)) {
                // Ignore <?xml and <!DOCTYPE stuff.
                $xml = preg_replace('/<\?xml version=\"1\.0\"\?><!DOCTYPE [^>]*>/', '', $xml);

                // Handle different mimetypes.
                $xml = str_replace('application/vnd.syncml-devinf+wbxml',
                                   'application/vnd.syncml-devinf+xml',
                                   $xml);
            }

            $this->assertEquals(Horde_String::lower($xml_ref), Horde_String::lower($xml));
        }
    }
}
