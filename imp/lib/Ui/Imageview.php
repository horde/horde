<?php
/**
 * This class is designed to provide a place to store common code shared among
 * various MIME Viewers relating to image viewing preferences.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Imageview
{
    /**
     * List of safe addresses.
     *
     * @var array
     */
    protected $_safeAddrs;

    /**
     * Show inline images in messages?
     *
     * @param IMP_Contents $contents  The contents object containing the
     *                                message.
     *
     * @return boolean  True if inline image should be shown.
     */
    public function showInlineImage(IMP_Contents $contents)
    {
        global $prefs, $registry;

        if (!$prefs->getValue('image_replacement')) {
            return true;
        }

        if (!$contents) {
            return false;
        }

        $from = Horde_Mime_Address::bareAddress($contents->getHeader()->getValue('from'));
        if ($registry->hasMethod('contacts/getField')) {
            $params = IMP::getAddressbookSearchParams();
            try {
                if ($registry->call('contacts/getField', array($from, '__key', $params['sources'], false, true))) {
                    return true;
                }
            } catch (Horde_Exception $e) {}
        }

        /* Check safe address list. */
        $this->_initSafeAddrList();
        return in_array($from, $this->_safeAddrs);
    }

    /**
     */
    protected function _initSafeAddrList()
    {
        if (!isset($this->_safeAddrs)) {
            $this->_safeAddrs = json_decode($GLOBALS['prefs']->getValue('image_replacement_addrs'));
            if (empty($this->_safeAddrs)) {
                $this->_safeAddrs = array();
            }
        }
    }

    /**
     */
    public function addSafeAddress($address)
    {
        $this->_initSafeAddrList();
        $this->_safeAddrs[] = $address;
        $this->_safeAddrs = array_keys(array_flip($this->_safeAddrs));
        $GLOBALS['prefs']->setValue('image_replacement_addrs', json_encode($this->_safeAddrs));
    }

}
