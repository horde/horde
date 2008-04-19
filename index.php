<?php
/**
 * $Horde: incubator/operator/index.php,v 1.1 2008/04/19 01:26:06 bklang Exp $
 *
 * Copyright 2008 Alkaloid Networks LLC <http://projects.alkaloid.net>
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

require OPERATOR_BASE . '/search.php';
