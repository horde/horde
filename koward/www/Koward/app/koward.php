<?php

/** Tell the registry to take everything from this application directory. */
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/..');
}

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/** Configure the Autoloader to handle the "Koward" pattern */
Horde_Autoloader::addClassPattern('/^Koward_/', 'Koward/');

/** Dispatch the request. */
try {
    Koward::dispatch(__FILE__);
} catch (Exception $e) {
    // @TODO Make nicer
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<pre>'; var_dump($e); echo '</pre>';
}
