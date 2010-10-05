#!/usr/bin/env php
<?php
if (strpos('@php_dir@', '@php_dir') === 0) {
    set_include_path(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());
}

/**
 * We are heavily relying on the PEAR libraries which are not clean with regard
 * to E_STRICT.
 */
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    error_reporting(E_ALL & ~E_STRICT);
} else {
    error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
}

require_once 'Horde/Autoloader/Default.php';
Components::main();
