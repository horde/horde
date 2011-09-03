<?php
/**
 * Sam external API interface.
 *
 * This file defines Sam's external API interface. Other applications
 * can interact with Sam through this API.
 */
class Sam_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'showBlacklist' => '%application%/blacklist.php',
        'showWhitelist' => '%application%/whitelist.php',
    );

    public function whitelistFrom($addresses)
    {
        $this->_listFrom($addresses, 'white');
    }

    public function blacklistFrom($addresses)
    {
        $this->_listFrom($addresses, 'black');
    }

    protected function _listFrom($addresses, $what)
    {
        $sam_driver = $GLOBALS['injector']->getInstance('Sam_Driver');

        if (!$sam_driver->hasCapability($what . 'list_from')) {
            return false;
        }

        $sam_driver->retrieve();
        $list = $sam_driver->getListOption($what . 'list_from');
        $list = explode("\n", $list);

        if (is_array($addresses)) {
            foreach ($addresses as $address) {
                if (!in_array($address, $list)) {
                    $list[] = $address;
                }
            }
        } elseif (!in_array($address, $list)) {
            $list[] = $addresses;
        }

        $sam_driver->setListOption($what . 'list_from', implode("\n", $list));

        $sam_driver->store();
    }
}
