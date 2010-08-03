<?php
/**
 * Identify the horde base application.
 */
require_once dirname(__FILE__) . '/../../koward/config/base.php';

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Core/Autoloader.php';

/* Configure the Autoloader to handle the "Koward" pattern */
$__autoloader->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Koward_/', 'Koward/'));

/* Dispatch the request. */
try {
    Koward::dispatch(__FILE__);
} catch (Exception $e) {
    global $notification, $registry;

    Horde::logMessage($e, 'DEBUG');

    if (isset($notification)) {
        $notification->push($e->getMessage(), 'horde.error');
    }

    if (isset($registry)) {
        $registry->get('webroot', 'koward')->redirect();
    }
}
