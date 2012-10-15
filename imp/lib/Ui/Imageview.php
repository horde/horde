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
     * Show inline images in messages?
     *
     * @param IMP_Contents $contents  The contents object containing the
     *                                message.
     *
     * @return boolean  True if inline image should be shown.
     */
    public function showInlineImage(IMP_Contents $contents)
    {
        global $injector, $prefs, $registry;

        if (!$prefs->getValue('image_replacement')) {
            return true;
        }

        if (!$contents ||
            !($from = $contents->getHeader()->getOb('from'))) {
            return false;
        }

        if ($registry->hasMethod('contacts/search')) {
            $sparams = $injector->getInstance('IMP_Ui_Contacts')->getAddressbookSearchParams();
            $res = $registry->call('contacts/search', array($from->bare_addresses, array(
                'fields' => $sparams['fields'],
                'returnFields' => array('email'),
                'rfc822Return' => true,
                'sources' => $sparams['sources']
            )));

            // Don't allow personal addresses by default - this is the only
            // e-mail address a Spam sender for sure knows you will recognize
            // so it is too much of a loophole.
            $res->setIteratorFilter(0, $injector->getInstance('IMP_Identity')->getAllFromAddresses());

            foreach ($from as $val) {
                if ($res->contains($val)) {
                    return true;
                }
            }
        }

        /* Check safe address list. */
        $safeAddrs = $injector->getInstance('IMP_Prefs_Special_ImageReplacement')->safeAddrList();
        foreach ($from as $val) {
            if ($safeAddrs->contains($val)) {
                return true;
            }
        }

        return false;
    }

}
