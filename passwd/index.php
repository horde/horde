<?php
/**
 * $Horde: passwd/index.php,v 1.15.2.5 2009/01/06 15:25:15 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */

@define('PASSWD_BASE', dirname(__FILE__));
$passwd_configured = (is_readable(PASSWD_BASE . '/config/conf.php') &&
                      is_readable(PASSWD_BASE . '/config/backends.php'));

if (!$passwd_configured) {
    require PASSWD_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Passwd', PASSWD_BASE, array('conf.php', 'backends.php'));
}

require PASSWD_BASE . '/main.php';
