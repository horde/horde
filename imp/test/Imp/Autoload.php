<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

Horde_Test_Autoload::addPrefix('IMP', __DIR__ . '/../../lib');

require_once 'Stub/HtmlViewer.php';
require_once 'Stub/ItipRequest.php';
