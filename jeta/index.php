<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Eric Rostetter <eric.rostetter@physics.utexas.edu>
 */

require_once dirname(__FILE__) . '/lib/base.load.php';
$jeta_configured = (is_readable(JETA_BASE . '/config/conf.php') &&
                    is_readable(JETA_BASE . '/config/prefs.php'));

if (!$jeta_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Jeta', JETA_BASE, array('conf.php'));
}

require JETA_BASE . '/main.php';
