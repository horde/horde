<?php
/**
 * Sam external API interface.
 *
 * This file defines SAM's external API interface. Other applications
 * can interact with Sam through this API.
 */

$_services['blacklistFrom'] = array(
    'args' => array('addresses' => '{urn:horde}stringArray'),
    'type' => 'boolean'
);

$_services['showBlacklist'] = array(
    'link' => '%application%/blacklist.php'
);

$_services['whitelistFrom'] = array(
    'args' => array('addresses' => '{urn:horde}stringArray'),
    'type' => 'boolean'
);

$_services['showWhitelist'] = array(
    'link' => '%application%/whitelist.php'
);

function _sam_whitelistFrom($addresses)
{
    require_once dirname(__FILE__) . '/../lib/base.php';

    global $sam_driver;
    if (!$sam_driver->hasCapability('whitelist_from')) {
        return false;
    }

    $sam_driver->retrieve();
    $list = $sam_driver->getListOption('whitelist_from');
    $list = preg_split("/\n/", $list);

    if (is_array($addresses)) {
        foreach ($addresses as $address) {
            if (!in_array($address, $list)) {
                $list[] = $address;
            }
        }
    } elseif (!in_array($address, $list)) {
        $list[] = $addresses;
    }

    $sam_driver->setListOption('whitelist_from', implode("\n", $list));
    return $sam_driver->store();
}

function _sam_blacklistFrom($addresses)
{
    require_once dirname(__FILE__) . '/../lib/base.php';

    global $sam_driver;
    if (!$sam_driver->hasCapability('blacklist_from')) {
        return false;
    }

    $sam_driver->retrieve();
    $list = $sam_driver->getListOption('blacklist_from');
    $list = preg_split("/\n/", $list);

    if (is_array($addresses)) {
        foreach ($addresses as $address) {
            if (!in_array($address, $list)) {
                $list[] = $address;
            }
        }
    } elseif (!in_array($address, $list)) {
        $list[] = $addresses;
    }

    $sam_driver->setListOption('blacklist_from', implode("\n", $list));
    return $sam_driver->store();
}
