<?php
/**
 * Horde_Autoloader_Default
 *
 * Default autoloader definition that simply uses the include path with default
 * class path mappers. Classes will be loaded according to PSR-0.
 *
 * PHP 5
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */
require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/Base.php';
require_once 'Horde/Autoloader/IncludePath.php';
require_once 'Horde/Autoloader/ClassPathMapper.php';
require_once 'Horde/Autoloader/ClassPathMapper/Default.php';

$__autoloader = new Horde_Autoloader_IncludePath();
$__autoloader->registerAutoloader();
