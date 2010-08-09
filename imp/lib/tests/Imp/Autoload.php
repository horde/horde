<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

if (!spl_autoload_functions()) {
    spl_autoload_register(
        create_function(
            '$class', 
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
            . 'if (substr($filename, 0, 10) == "IMP/Horde/") {'
            . '  $filename = substr($filename, 10);'
            . '  $filename = dirname(__FILE__) . "/../../$filename";'
            . '} else if (substr($filename, 0, 4) == "IMP/") {'
            . '  $filename = substr($filename, 4);'
            . '  $filename = dirname(__FILE__) . "/../../$filename";'
            . '}'
            . '$err_mask = E_ALL ^ E_WARNING;'
            . '$oldErrorReporting = error_reporting($err_mask);'
            . 'include "$filename.php";'
            . 'error_reporting($oldErrorReporting);'
        )
    );
}

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);
