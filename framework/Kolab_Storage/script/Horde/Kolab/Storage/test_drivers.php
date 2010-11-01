<?php
/**
 * Test script for the Kolab storage drivers.
 *
 * Usage:
 *   test_drivers.php -u [username]
 *                    -p [password]
 *                   [-H [hostname]]
 *                   [-d [imap_client log file]]
 *
 * Username/password/hostspec on the command line will override the $params
 * values.
 * Driver on the command line will override the $driver value.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

require_once 'Horde/Autoloader.php';

/** Setup command line */
$p = new Horde_Argv_Parser(
    array(
        'optionList' =>
        array(
            new Horde_Argv_Option(
                '-u',
                '--user',
                array(
                    'help' => 'The user name.',
                    'type' => 'string',
                    'nargs' => 1
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--pass',
                array(
                    'help' => 'The password.',
                    'type' => 'string',
                    'nargs' => 1
                )
            ),
            new Horde_Argv_Option(
                '-H',
                '--host',
                array(
                    'help' => 'The host to connect to.',
                    'type' => 'string',
                    'nargs' => 1,
                    'default' => 'localhost'
                )
            ),
            new Horde_Argv_Option(
                '-d',
                '--debug',
                array(
                    'help' => 'The path to the IMAP client debug file.',
                    'type' => 'string',
                    'nargs' => 1
                )
            ),
        )
    )
);

/** Handle arguments */
try {
    list($options, $args) = $p->parseArgs();
} catch (InvalidArgumentException $e) {
    print $e->getMessage() . "\n\n" . $p->getUsage() . "\n\n";
}

/** Setup shared test fixture */
$fixture = new stdClass;
$fixture->conf = $options;
$fixture->drivers = array();

$all_tests = '@PHP-TEST-DIR@/Kolab_Storage/Horde/Kolab/Storage/AllTests.php';
if (strpos($all_tests, '@PHP-TEST-DIR') !== false) {
    $all_tests = dirname(__FILE__)
        . '/../../../../test/Horde/Kolab/Storage/AllTests.php';
}

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

require_once $all_tests;

$suite = Horde_Kolab_Storage_AllTests::suite();
$suite->setSharedFixture($fixture);

PHPUnit_TextUI_TestRunner::run($suite);