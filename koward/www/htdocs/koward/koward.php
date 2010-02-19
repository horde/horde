<?php
/**
 * Identify the horde base application.
 */
require_once dirname(__FILE__) . '/../../koward/config/base.php';

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
    global $notification, $registry;

    Horde::logMessage($e, 'DEBUG');

    if (isset($notification)) {
        $notification->push($e->getMessage(), 'horde.error');
    }

    if (isset($registry)) {
        header('Location: ' . $registry->get('webroot', 'koward'));
    }
}
