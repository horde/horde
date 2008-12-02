<?php

require_once 'Horde/Maintenance.php';
require_once dirname(__FILE__) . '/../base.php';

/**
 * The Maintenance_IMP class defines the maintenance operations run upon
 * login to IMP.
 *
 * Copyright 2001-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Maintenance
 */
class Maintenance_IMP extends Maintenance {

    /**
     * Hash holding maintenance preference names.
     *
     * @var array
     */
    var $maint_tasks = array(
        'tos_agreement'              => MAINTENANCE_FIRST_LOGIN,
        'fetchmail_login'            => MAINTENANCE_EVERY,
        'rename_sentmail_monthly'    => MAINTENANCE_MONTHLY,
        'delete_sentmail_monthly'    => MAINTENANCE_MONTHLY,
        'delete_attachments_monthly' => MAINTENANCE_MONTHLY,
        'purge_sentmail'             => MAINTENANCE_MONTHLY,
        'purge_spam'                 => MAINTENANCE_MONTHLY,
        'purge_trash'                => MAINTENANCE_MONTHLY
    );

}
