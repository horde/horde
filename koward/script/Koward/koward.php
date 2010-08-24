#!@php_bin@
<?php
/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader/Default.php';

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
$cli = Horde_Cli::init();

/**
 * Hm, the fact that we need the registry at this point for
 * identifying the location where we installed the application is not
 * really satisfying. We need it to know the location of the
 * configuration though.
 */
$koward_authentication = 'none';
require_once $opts->base . '/koward/config/base.php';

/**
 * FIXME END
 */

/* Configure the Autoloader to handle the "Koward" pattern */
$__autoloader->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Koward_/', 'Koward/'));

/* Dispatch the request. */
try {
  Koward::dispatch($opts->base . '/htdocs/koward/koward.php', 'Koward_Cli');
} catch (Exception $e) {
    // @TODO Make nicer
    echo '<h1>' . $e->getMessage() . '</h1>';
    echo '<pre>'; var_dump($e); echo '</pre>';
}
