<?php
/**
 *
 * This product includes software developed by the Horde Project (http://www.horde.org/).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andre Pawlowski aka sqall <sqall@h4des.org>
 */

@define('KASTALIA_BASE', dirname(__FILE__));
$kastalia_configured = (is_readable(KASTALIA_BASE . '/config/conf.php'));

if (!$kastalia_configured) {
    require KASTALIA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Kastalia', KASTALIA_BASE,
                                   array('conf.php'));
}

require KASTALIA_BASE . '/list.php';
require KASTALIA_BASE . '/main.php';
