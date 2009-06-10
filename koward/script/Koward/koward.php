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
$cli = Horde_Cli::singleton();
$cli->init();

/**
 * Hm, the fact that we need the registry at this point for
 * identifying the location where we installed the application is not
 * really satisfying. We need it to know the location of the
 * configuration though.
 */
require_once $opts->base . '/koward/config/base.php';

/**
 * FIXME END
 */

/** Configure the Autoloader to handle the "Koward" pattern */
Horde_Autoloader::addClassPattern('/^Koward_/', 'Koward/');

/** Dispatch the request. */
try {
  Koward::dispatch($opts->base . '/htdocs/koward/koward.php', 'Koward_Cli');
} catch (Exception $e) {
    // @TODO Make nicer
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<pre>'; var_dump($e); echo '</pre>';
}
