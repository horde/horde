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
     * @var Horde_Mail_Rfc822_List
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
        global $injector, $prefs, $registry, $session;

        if (!$prefs->getValue('image_replacement')) {
            return true;
        }

        if (!$contents) {
            return false;
        }

        $from = $contents->getHeader()->getOb('from');

        if ($session->get('imp', 'csearchavail')) {
            $sparams = IMP::getAddressbookSearchParams();
            $apiargs = array(
                $from->bare_addresses,
                $sparams['sources'],
                $sparams['fields'],
                false,
                false,
                array('name', 'email')
            );

            $ajax = new IMP_Ajax_Imple_ContactAutoCompleter();
            $res = $ajax->parseContactsSearch($registry->call('contacts/search', $apiargs));

            // Don't allow personal addresses by default - this is the only
            // e-mail address a Spam sender for sure knows you will recognize
            // so it is too much of a loophole.
            $res->setIteratorFilter(0, array_keys($injector->getInstance('IMP_Identity')->getAllFromAddresses(true)));

            foreach ($from as $val) {
                if ($res->contains($val)) {
                    return true;
                }
            }
        }

        /* Check safe address list. */
        $this->_initSafeAddrList();
        foreach ($from as $val) {
            if ($this->_safeAddrs->contains($val)) {
                return true;
            }
        }

        return false;
    }

    /**
     */
    protected function _initSafeAddrList()
    {
        if (!isset($this->_safeAddrs)) {
            $this->_safeAddrs = new Horde_Mail_Rfc822_List(json_decode($GLOBALS['prefs']->getValue('image_replacement_addrs')));
        }
    }

    /**
     */
    public function addSafeAddress($address)
    {
        $this->_initSafeAddrList();
        $this->_safeAddrs->add($address);
        $this->_safeAddrs->unique();
        $GLOBALS['prefs']->setValue('image_replacement_addrs', json_encode($this->_safeAddrs->bare_addresses));
    }

}
