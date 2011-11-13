<?php
/**
 * A sample script for accessing pear.horde.org.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Horde_Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Pear
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader/Default.php';

$pear = new Horde_Pear_Remote();

print($pear->getChannel());

print "\n\n";

print(join("\n", $pear->listPackages()));

print "\n\n";

print($pear->getLatestRelease('Horde_Core'));

print "\n\n";

print($pear->getLatestDownloadUri('Horde_Core'));

print "\n\n";

print_r($pear->getLatestDetails('Horde_Core'));

print "\n\n";

print($pear->releaseExists('Horde_Core', '1.7.0'));

print "\n\n";

print(count($pear->getDependencies('Horde_Exception', '1.0.0')));

print "\n\n";

print($pear->getPackageXml('Horde_Exception', '1.0.0')->getName());

print "\n\n";

$pear = new Horde_Pear_Remote('pear.phpunit.de');
print(join("\n", $pear->listPackages()));

print "\n\n";