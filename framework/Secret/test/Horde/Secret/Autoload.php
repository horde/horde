<?php
/**
 * Setup autoloading for the tests.
 *
 * @category Horde
 * @package  Secret
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Secret
 */

if (!spl_autoload_functions()) {
    spl_autoload_register(
        create_function(
            '$class',
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
            . '$err_mask = E_ALL ^ E_WARNING;'
            . '$oldErrorReporting = error_reporting($err_mask);'
            . 'include "$filename.php";'
            . 'error_reporting($oldErrorReporting);'
        )
    );
}

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);

/** Needed for PEAR_Error. */
@require_once 'PEAR.php';
