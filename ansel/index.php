<?php
/**
 * Copyright 2001-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');


/* Load mobile? */
$mode = $session->get('horde', 'mode');
if ($mode == 'smartmobile' || $mode == 'mobile') {
    include ANSEL_BASE . '/mobile.php';
    exit;
}

Ansel::getUrlFor('default_view', array())->redirect();
