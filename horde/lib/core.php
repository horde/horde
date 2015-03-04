<?php
/**
 * Horde Application Framework core services file.
 *
 * This file sets up any necessary include path variables and includes
 * the minimum required Horde libraries.
 *
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

/* Turn PHP stuff off that can really screw things up. */
ini_set('allow_url_include', 0);
ini_set('tidy.clean_output', 0);

// TODO: Removed from PHP as of 5.4.0
if (version_compare(PHP_VERSION, '5.4', '<')) {
    ini_set('magic_quotes_runtime', 0);
    ini_set('magic_quotes_sybase', 0);
}

/* Exit immediately if register_globals is active.
 * register_globals may return 'Off' on some systems. See Bug #10062. */
if (($rg = ini_get('register_globals')) && (strcasecmp($rg, 'off') !== 0)) {
    exit('Register globals is enabled. Exiting.');
}

$dirname = __DIR__;

if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', $dirname . '/..');
}

set_include_path($dirname . PATH_SEPARATOR . get_include_path());

/* Autoloader class path mappers can be added inside horde.local.php in the
 * $__horde_autoload_cpm array. Each element of this array contains two
 * values: the ClassPathMapper class name and an array of arguments to that
 * object's constructor. */
$__horde_autoload_cpm = array();
if (file_exists(HORDE_BASE . '/config/horde.local.php')) {
    include_once HORDE_BASE . '/config/horde.local.php';
}

/* Set up autoload paths for core Horde libs (located in lib/). This can't
 * be defined in Horde_Autoloader since the current directory path can not be
 * determined there. */
if (!@include_once 'Horde/Autoloader/Cache.php') {
    require_once 'Horde/Autoloader/Default.php';
}

/* Add autoloaders. */
$__autoloader->addClassPathMapper(
    new Horde_Autoloader_ClassPathMapper_PrefixString('Horde', $dirname)
);
foreach ($__horde_autoload_cpm as $val) {
    $reflection = new ReflectionClass($val[0]);
    $__autoloader->addClassPathMapper(
        $reflection->newInstanceArgs($val[1])
    );
}
unset($__horde_autoload_cpm);

/* Sanity checking - if we can't even load the Horde_ErrorHandler file, then
 * the installation is all sorts of busted. */
if (!class_exists('Horde_ErrorHandler')) {
    exit('Cannot autoload Horde Core libraries. Please reinstall Horde and/or correctly configure the install paths. If you are using an autoloader cache, try to clear it.');
}

/* Default exception handler for uncaught exceptions. The default fatal
 * exception handler output may include things like passwords, etc. so don't
 * output this unless an admin. */
set_exception_handler(array('Horde_ErrorHandler', 'fatal'));

/* Catch errors. */
set_error_handler(array('Horde_ErrorHandler', 'errorHandler'), E_ALL | E_STRICT);

/* Catch fatal errors. */
register_shutdown_function(array('Horde_ErrorHandler', 'catchFatalError'));
