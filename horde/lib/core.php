<?php
/**
 * Horde Application Framework core services file.
 *
 * This file sets up any necessary include path variables and includes
 * the minimum required Horde libraries.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */

/* Turn PHP stuff off that can really screw things up. */
ini_set('allow_url_include', 0);
ini_set('magic_quotes_runtime', 0);
ini_set('magic_quotes_sybase', 0);
ini_set('tidy.clean_output', 0);
ini_set('zend.ze1_compatibility_mode', 0);

/* Exit immediately if register_globals is active. */
if (ini_get('register_globals')) {
    exit('Register globals is enabled. Exiting.');
}

$dirname = dirname(__FILE__);

if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', $dirname . '/..');
}

ini_set('include_path', $dirname . PATH_SEPARATOR . ini_get('include_path'));
if (file_exists(HORDE_BASE . '/config/horde.local.php')) {
    include_once HORDE_BASE . '/config/horde.local.php';
}

/* Set up autoload paths for core Horde libs (located in lib/). This can't
 * be defined in Horde_Autoloader since the current directory path can not be
 * determined there. */
require_once 'Horde/Autoloader/Default.php';
$__autoloader->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Horde(?:$|_)/i', $dirname));

/* Default exception handler for uncaught exceptions. The default fatal
 * exception handler output may include things like passwords, etc. so don't
 * output this unless an admin. */
set_exception_handler(array('Horde', 'fatal'));

/* Catch and log E_DEPRECATED errors. */
if (defined('E_DEPRECATED')) {
    set_error_handler(array('Horde', 'logDeprecated'), E_DEPRECATED);
}
