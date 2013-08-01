<?php
/**
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */

require_once 'Horde/Autoloader/Default.php';
require_once 'Horde/Autoloader/Cache.php';
require_once 'Horde/Autoloader/Cache/Backend.php';
require_once 'Horde/Autoloader/Cache/Backend/Apc.php';
require_once 'Horde/Autoloader/Cache/Backend/Eaccelerator.php';
require_once 'Horde/Autoloader/Cache/Backend/Tempfile.php';
require_once 'Horde/Autoloader/Cache/Backend/Xcache.php';

/**
 * Default cached autoloader definition that uses the include path with
 * default class path mappers. Classes will be loaded according to PSR-0 and
 * the caching backend will be automatically determined.
 *
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */

$__autoloader = new Horde_Autoloader_Cache(new Horde_Autoloader_IncludePath());
$__autoloader->registerAutoloader();
