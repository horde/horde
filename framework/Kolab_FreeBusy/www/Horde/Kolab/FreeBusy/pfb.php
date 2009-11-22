<?php
/**
 * A script for triggering an update of the Kolab Free/Busy information.
 *
 * This script generates partial free/busy information based on a
 * single calendar folder on the Kolab groupware server. The partial
 * information is cached and later assembled for display by the
 * freebusy.php script.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * @author  Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author  Gunnar Wrobel <p@rdus.de>
 * @author  Thomas Arendsen Hein <thomas@intevation.de>
 * @package Kolab_FreeBusy
 */

/** Load the required free/busy library */
require_once 'Horde/Kolab/FreeBusy.php';

/** Load the configuration */
require_once 'config.php';

$fb = new Horde_Kolab_FreeBusy();
$view = $fb->trigger();
$view->render();
