#!@php_bin@
<?php

define('AUTH_HANDLER', true);

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * FIXME START
 *
 * Simplify the Autoloader configuration so that the user needs no knowledge of
 * the app root.
 */

$options = array(
    new Horde_Argv_Option('-b', '--base', array('type' => 'string')),
);
$parser = new Horde_Argv_Parser(
    array(
        'allowUnknownArgs' => true,
        'optionList' => $options
    )
);
list($opts, $cmd) = $parser->parseArgs();

if (!$opts->base) {
    throw new InvalidArgumentException('The path to the application base has not been specified!');
}

/**
 * Ensure that the base parameters (especially SERVER_NAME) get set for the
 * command line.
 */
$cli = Horde_CLI::singleton();
$cli->init();

/**
 * Hm, the fact that we need the registry at this point for identifying the
 * location where we installed the application is not really satisfying. The
 * whole "app" dir should probably be in "lib", too. That way we can install it
 * in a defined location via PEAR.
 */
require_once $opts->base . '/lib/base.php';

/** Configure the Autoloader */
$app = 'koward';
Horde_Autoloader::addClassPattern('/^Koward_/', $registry->get('fileroot', $app) . '/lib/');
Horde_Autoloader::addClassPattern('/^Koward_/', $registry->get('fileroot', $app) . '/app/controllers/');

/**
 * FIXME END
 */

/** Initialize the command line handler */
$request = new Koward_Cli();

/**
 * The following is an extract from rampage.php and only a rough draft. Calling
 * Controller actions this way is still rather complicated on the command line
 * this way. For one thing you need to provide the application installation base
 * which is knowledge we'd rather hide from the user and the full path to the
 * Controller action (including the path to the app identified by the registry)
 * has to be provided. This should be handled via other CLI parameters.
 */
$mapper = new Horde_Routes_Mapper();

$routeFile = $registry->get('fileroot', $app) . '/config/routes.php';
if (!file_exists($routeFile)) {
    throw new Horde_Controller_Exception('Not routable');
}
$mapper->prefix = $registry->get('webroot', 'horde') . '/' . $app;
include $routeFile;

$context = array(
    'mapper' => $mapper,
    'controllerDir' => $registry->get('fileroot', $app) . '/app/controllers',
    'viewsDir' => $registry->get('fileroot', $app) . '/app/views',
    // 'logger' => '',
);

try {
    $dispatcher = Horde_Controller_Dispatcher::singleton($context);
    $dispatcher->dispatch($request);
} catch (Exception $e) {
    // @TODO Make nicer
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<pre>'; var_dump($e); echo '</pre>';
}

exit(0);
