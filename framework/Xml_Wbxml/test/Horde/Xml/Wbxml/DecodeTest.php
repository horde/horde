<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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

        foreach (glob(dirname(__FILE__) . '/../../../../doc/examples/*.wbxml') as $file) {
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

                // Hack to fix wrong mimetype.
                $xml = str_replace(
                    array('application/vnd.syncml-devinf+wbxml',
                          'xmlns="syncml:metinf1.0"',
                          'xmlns="syncml:devinf1.0"',
                          'xmlns="syncml:metinf1.1"',
                          'xmlns="syncml:devinf1.1"'),
                    array('application/vnd.syncml-devinf+xml',
                          'xmlns="syncml:metinf"',
                          'xmlns="syncml:devinf"',
                          'xmlns="syncml:metinf"',
                          'xmlns="syncml:devinf"'),
                    $xml);
            }

            $this->assertEquals(strtolower($xml_ref), strtolower($xml));
        }
    }
}
