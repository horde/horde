<?php
/**
 * Operator external API interface.
 *
 * This file defines Operator's external API interface. Other applications
 * can interact with Operator through this API.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Operator
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');

function _operator_perms()
{
    static $perms = array();

    if (!empty($perms)) {
        return $perms;
    }

    @define('OPERATOR_BASE', __DIR__ . '/..');
    require_once OPERATOR_BASE . '/lib/base.php';

    $perms['tree']['operator']['accountcodes'] = false;
    $perms['title']['operator:accountcodes'] = _("Account Codes");

    $accountcodes = Operator::getAccountCodes();
    foreach ($accountcodes as $accountcode) {
        $perms['tree']['operator']['accountcodes'][$accountcode] = false;
        $perms['title']['operator:accountcodes:' . $accountcode] = $accountcode;
    }

    return $perms;
}
