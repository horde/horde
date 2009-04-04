<?php
/**
 * $Horde: fima/index.php,v 1.0 2008/04/25 17:59:00 trt Exp $
 *
 * Copyright 2008 Thomas Trethan <thomas@trethan.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Thomas Trethan <thomas@trethan.net>
 */

@define('FIMA_BASE', dirname(__FILE__));
$fima_configured = (is_readable(FIMA_BASE . '/config/conf.php') &&
                    is_readable(FIMA_BASE . '/config/prefs.php'));

if (!$fima_configured) {
    require FIMA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Fima', FIMA_BASE,
                                   array('conf.php', 'prefs.php'));
}

require FIMA_BASE . '/postings.php';
