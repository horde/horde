<?php
/**
 * Initialize testing for this application.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Koward
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader/Default.php';

if (!defined('KOWARD_BASE')) {
    define('KOWARD_BASE', __DIR__ . '/../');
}

/* Set up the application class and controller loading */
$__autoloader->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Koward_/', KOWARD_BASE . '/lib/'));
