#!/usr/bin/env php
<?php
/**
 * Tests all .wbxml files in ../docs/examples/ by decoding them and
 * comparing to wbxml2xml's output.
 *
 * If filenames are provided on the command line they are used instead
 * of the complete examples/ directory.
 *
 * Errors are displayed as long single lines of XML files. View with
 * line wrapping turned off to see differences.
 *
 * @package Xml_Wbxml
 */

if (!is_executable('/usr/bin/wbxml2xml')) {
    die("/usr/bin/wbxml2xml is required for comparison tests.\n");
}

include_once dirname(__FILE__) . '/../WBXML/Decoder.php';
$decoder = new Horde_Xml_Wbxml_Decoder();

$files = array();
if ($argc > 1) {
    for ($i = 1; $i <= $argc; ++$i) {
        if (is_readable($argv[$i])) {
            $files[] = $argv[$i];
        }
    }
} else {
    $dir = dirname(__FILE__) . '/../docs/examples/';
    $d = dir($dir);
    while (false !== ($entry = $d->read())) {
        if (preg_match('/\.wbxml$/', $entry)) {
            $files[] = $dir . $entry;
        }
    }
    $d->close();
}

foreach ($files as $file) {
    $xml_ref = shell_exec('/usr/bin/wbxml2xml' . ' -m 0 -o - "' . $file . '" 2>/dev/null');
    $xml_ref = preg_replace(
        array(
            // Ignore <?xml and <!DOCTYPE stuff:
            '/<\?xml version=\"1\.0\"\?><!DOCTYPE [^>]*>/',
            // Normalize empty tags.
            '|<([^>]+)/>|'),
        array('', '<$1></$1>'),
        $xml_ref);

    $wbxml_in = file_get_contents($file, 'rb');

    $xml = $decoder->decodeToString($wbxml_in);

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

    if (is_string($xml) && strcasecmp($xml, $xml_ref) === 0) {
        echo "decode ok: $file\n";
    } else {
        echo "\ndecode FAILED: $file\nlibwbxml:  $xml_ref\n";
        if (is_string($xml)) {
            echo "Horde_Xml_Wbxml: $xml\n";
        } elseif (is_a($xml, 'PEAR_Error')) {
            echo "Horde_Xml_Wbxml: " . $xml->getMessage() . "\n";
        } else {
            echo "libwbxml:\n";
            var_dump($xml);
        }
        echo "\n";
    }
}
