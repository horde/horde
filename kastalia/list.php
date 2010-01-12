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
require_once KASTALIA_BASE . '/lib/base.php';

$title = _("List");

require KASTALIA_TEMPLATES . '/common-header.inc';
require KASTALIA_TEMPLATES . '/menu.inc';
