<?php
/**
 * Vilma external API interface.
 *
 * This file defines Vilma's external API interface. Other applications
 * can interact with Vilma through this API.
 *
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net/>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */
@define('VILMA_BASE', dirname(__FILE__) . '/..');

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');


$_services['listDomains'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');

function _vilma_listDomains()
{
    require_once VILMA_BASE . '/lib/base.php';
    global $vilma_driver;

    return $vilma_driver->getDomains();
    $domains = array();
    foreach ($vilma_driver->getDomains() as $domain) {
        $domains[] = $domain['domain_name'];
    }
    return $domains;
}
