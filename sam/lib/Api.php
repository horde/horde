<?php
/**
 * Sam external API interface.
 *
 * This file defines Sam's external API interface. Other applications
 * can interact with Sam through this API.
 */
class Sam_Api extends Horde_Registry_Api
{
    public function whitelistFrom($addresses)
    {
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

    public function blacklistFrom($addresses)
    {
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
}
