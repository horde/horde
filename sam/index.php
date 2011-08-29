<?php
/*
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 */

@define('SAM_BASE', dirname(__FILE__));
$sam_configured = (is_readable(SAM_BASE . '/config/conf.php') &&
                   is_readable(SAM_BASE . '/config/attributes.php') &&
                   is_readable(SAM_BASE . '/config/backends.php'));

if (!$sam_configured) {
    require SAM_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('SAM', SAM_BASE,
        array('conf.php', 'backends.php'),
        array('attributes.php' => 'This file lists all of the form options ' .
                                  'available to SAM.'));
}

require_once SAM_BASE . '/lib/base.php';
if ($conf['enable']['rules']) {
    require SAM_BASE . '/spam.php';
} else {
    require SAM_BASE . '/blacklist.php';
}
