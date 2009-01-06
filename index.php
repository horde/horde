<?php
/**
 * $Horde: incubator/operator/index.php,v 1.4 2009/01/06 17:51:06 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('OPERATOR_BASE', dirname(__FILE__));
$operator_configured = (is_readable(OPERATOR_BASE . '/config/conf.php'));

if (!$operator_configured) {
    require OPERATOR_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Operator', OPERATOR_BASE,
                                   array('conf.php'));
}

require OPERATOR_BASE . '/viewgraph.php';
