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
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */

require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/Base.php';
require_once 'Horde/Autoloader/IncludePath.php';
require_once 'Horde/Autoloader/ClassPathMapper.php';
require_once 'Horde/Autoloader/ClassPathMapper/Default.php';

/**
 * Default autoloader definition that simply uses the include path with
 * defualt class path mappers. Classes will be loaded according to PSR-0.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */

$__autoloader = new Horde_Autoloader_IncludePath();
$__autoloader->registerAutoloader();
