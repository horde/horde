<?php
/**
 * Horde_Autoloader_CacheDefault
 *
 * Default cached autoloader definition that uses the include path with default
 * class path mappers. Classes will be loaded according to PSR-0 and the caching backend will be automatically determined.
 *
 * PHP 5
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */
require_once 'Horde/Autoloader/Default.php';
require_once 'Horde/Autoloader/CacheDefault.php';

$__autoloader = new Horde_Autoloader_Cache();
$__autoloader->registerAutoloader();
