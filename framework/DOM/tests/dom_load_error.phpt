--TEST--
Check that Horde::DOM handles load errors gracefully.
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

require_once dirname(__FILE__) . '/../DOM.php';

// Load XML
$xml = file_get_contents(dirname(__FILE__) . '/fixtures/load_error.xml');

$params = array('xml' => $xml, 'options' => HORDE_DOM_LOAD_REMOVE_BLANKS);

$dom = Horde_DOM_Document::factory($params);

// Check that the xml loading elicits an error
var_dump(is_a($dom, 'PEAR_Error'));

// Load XML
$xml = file_get_contents(dirname(__FILE__) . '/fixtures/load_ok.xml');

$params = array('xml' => $xml, 'options' => HORDE_DOM_LOAD_REMOVE_BLANKS);

$dom = Horde_DOM_Document::factory($params);

// Check that the xml loading elicits an error
var_dump(is_a($dom, 'PEAR_Error'));

--EXPECT--
bool(true)
bool(false)
