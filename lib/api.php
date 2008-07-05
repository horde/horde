<?php
/**
 * Operator external API interface.
 *
 * This file defines Operator's external API interface. Other applications
 * can interact with Operator through this API.
 *
 * $Horde: incubator/operator/lib/api.php,v 1.1 2008/07/05 15:53:39 bklang Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
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

    @define('OPERATOR_BASE', dirname(__FILE__) . '/..');
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
