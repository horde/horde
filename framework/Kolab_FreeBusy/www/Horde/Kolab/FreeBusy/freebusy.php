<?php
/**
 * The web entry point for the Kolab free/busy system.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Thomas Arendsen Hein <thomas@intevation.de>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/** Dispatch the request. */
$params = array('config' => array('dir' => __DIR__ . '/config'));
$application = Horde_Kolab_FreeBusy::singleton($params);
$application->dispatch();
