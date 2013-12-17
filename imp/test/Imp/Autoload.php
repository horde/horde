<?php
/**
 * Setup autoloading for the tests.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */

Horde_Test_Autoload::addPrefix('IMP', __DIR__ . '/../../lib');

require_once 'Stub/HtmlViewer.php';
require_once 'Stub/Imap.php';
require_once 'Stub/ItipRequest.php';
