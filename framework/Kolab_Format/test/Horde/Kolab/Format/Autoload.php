<?php
/**
 * Setup autoloading for the tests.
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

if (!spl_autoload_functions()) {
    spl_autoload_register(
        create_function(
            '$class',
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);
             $err_mask = E_ALL ^ E_WARNING;
             $oldErrorReporting = error_reporting($err_mask);
             include "$filename.php";
             error_reporting($oldErrorReporting);'
        )
    );
}

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Load the basic test definition */
//require_once dirname(__FILE__) . '/TestCase.php';
