<?php
/**
 * Skeleton index script.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Your Name <you@example.com>
 */

require_once dirname(__FILE__) . '/lib/base.load.php';
$skeleton_configured = (is_readable(SKELETON_BASE . '/config/conf.php') &&
                        is_readable(SKELETON_BASE . '/config/prefs.php'));

if (!$skeleton_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Skeleton', SKELETON_BASE,
                                   array('conf.php', 'prefs.php'));
}

require SKELETON_BASE . '/list.php';
