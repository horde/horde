<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');


/* Load mobile? */
if (in_array($registry->getView(), array(Horde_Registry::VIEW_MINIMAL, Horde_Registry::VIEW_SMARTMOBILE))) {
    include ANSEL_BASE . '/smartmobile.php';
    exit;
}

Ansel::getUrlFor('default_view', array())->redirect();
