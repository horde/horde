<?php
/**
 * Horde Application Framework core services file.
 *
 * This file sets up any necessary include path variables and includes
 * the minimum required Horde libraries.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/* Turn PHP stuff off that can really screw things up. */
ini_set('magic_quotes_sybase', 0);
ini_set('magic_quotes_runtime', 0);
ini_set('zend.ze1_compatibility_mode', 0);
ini_set('allow_url_include', 0);

/* Unset all variables populated through register_globals. */
if (ini_get('register_globals')) {
    foreach (array($_GET, $_POST, $_COOKIE, $_ENV, $_SERVER) as $var) {
        foreach (array_keys($var) as $key) {
            unset($$key);
        }
    }
}

$horde_dir = dirname(__FILE__);
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', $horde_dir . '/..');
}

/* Define any local include_path alterations in horde/config/horde.local.php.
 * Example:
 *   ini_set('include_path', dirname(__FILE__) . PATH_SEPARATOR . ini_get('include_path'));
 */
if (file_exists($horde_dir . '/../config/horde.local.php')) {
    include $horde_dir . '/../config/horde.local.php';
}

/* PEAR base class. */
include_once 'PEAR.php';

/* Log (need to include because of constants). */
include_once 'Log.php';

/* Horde Autoloader. */
include_once 'Horde/Autoloader.php';

/* Set up autoload paths for core Horde libs (located in lib/). This can't
 * be defined in Horde_Autoloader since the $horde_dir path can not be
 * determined there. */
Horde_Autoloader::addClassPattern('/^Horde(?:$|_)/i', $horde_dir);

/* Default exception handler for uncaught exceptions. The default fatal
 * exception handler output may include things like passwords, etc. so don't
 * output this unless an admin. */
set_exception_handler(array('Horde', 'fatal'));

/* Browser detection object. */
$browser = Horde_Browser::singleton();
