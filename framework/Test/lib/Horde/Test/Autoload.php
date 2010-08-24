<?php
/**
 * Reduced Horde Autoloader for test suites.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Test
 * @subpackage UnitTests
 */
$autoloaders = spl_autoload_functions();
if (!empty($autoloaders)) {
    /**
     * Ignore autoloaders which are incapable of loading Horde
     * classes (e.g. PHPUnit >= 3.5.0)
     */
    $autoloaders = array_diff($autoloaders, array('phpunit_autoload'));
}
if (empty($autoloaders)) {
    spl_autoload_register(
        create_function(
            '$class',
            '$filename = str_replace(array(\'::\', \'_\'), \'/\', $class);'
            . '$err_mask = E_ALL ^ E_WARNING;'
            . '$oldErrorReporting = error_reporting($err_mask);'
            . 'include "$filename.php";'
            . 'error_reporting($oldErrorReporting);'
        )
    );
}
