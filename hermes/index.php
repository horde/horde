<?php
/**
 * $Horde: hermes/index.php,v 1.16 2009/01/06 17:50:08 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('HERMES_BASE', dirname(__FILE__));
$hermes_configured = (is_readable(HERMES_BASE . '/config/conf.php'));

if (!$hermes_configured) {
    require HERMES_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Hermes', HERMES_BASE,
        array('conf.php'));
}

require HERMES_BASE . '/time.php';
