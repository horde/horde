<?php
/**
 * A script for fetching the Kolab Free/Busy information.
 *
 * $Horde: framework/Kolab_FreeBusy/www/Horde/Kolab/FreeBusy/freebusy.php,v 1.6 2009/07/14 00:28:33 mrubinsk Exp $
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
$view = $fb->fetch();
$view->render();

