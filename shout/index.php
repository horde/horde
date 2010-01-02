<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */
@define('SHOUT_BASE', dirname(__FILE__));
$shout_configured = (is_readable(SHOUT_BASE . '/config/conf.php'));

if (!$shout_configured) {
require SHOUT_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Shout', SHOUT_BASE,
        array('conf.php'));
}

require_once SHOUT_BASE . '/lib/base.php';
header('Location: ' . Horde::applicationUrl('extensions.php'));
