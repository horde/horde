<?php
/**
 * $Horde: ansel/index.php,v 1.22 2009/01/06 17:48:49 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

define('ANSEL_BASE', dirname(__FILE__));
$ansel_configured = (is_readable(ANSEL_BASE . '/config/conf.php') &&
                     is_readable(ANSEL_BASE . '/config/prefs.php') &&
                     is_readable(ANSEL_BASE . '/config/styles.php'));

if (!$ansel_configured) {
    require ANSEL_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Ansel', ANSEL_BASE,
        array('conf.php', 'prefs.php'),
        array('styles.php' => 'This file controls the available gallery styles for Ansel.'));
}

require_once ANSEL_BASE . '/lib/base.php';
header('Location: ' . Ansel::getUrlFor('default_view', array()));
exit;
