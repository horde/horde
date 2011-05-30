<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Content
 */

require_once 'Horde/Test/Autoload.php';

// @TODO: Looks like this was meant to be doable by define $mappings before
// including Horde/Test/Autoload.php, but it doesn't work. In fact, the above
// require_once statment is redundant - Autoload.php is already included by the
// time we get here (at least for me...).
$mappings = array(
    'Content_Test' => dirname(__FILE__),
    'Content' => dirname(__FILE__) . '/../../lib');
$mapping = '';
if (!empty($mappings)) {
    foreach ($mappings as $prefix => $path) {
        $mapping .= 'if ($filename == "' . $prefix . '") {'
            . '  $filename = "' . $path . '$filename";'
            . '}';
        $mapping .= 'if (substr($filename, 0, ' . strlen($prefix) . ') == "' . $prefix . '") {'
            . '  $filename = substr($filename, ' . strlen($prefix) . ');'
            . '  $filename = "' . $path . '$filename";'
            . '}';
    }
    unset($mappings);
}
spl_autoload_register(
    create_function(
        '$class',
        '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
        . $mapping
        . '$err_mask = error_reporting() & ~E_WARNING;'
        . '$oldErrorReporting = error_reporting($err_mask);'
        . 'include "$filename.php";'
        . 'error_reporting($oldErrorReporting);'
    )
);
unset ($mapping);

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);
