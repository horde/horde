<?php
/**
 * This class is designed to provide a place to store common code shared among
 * various MIME Viewers relating to image viewing preferences.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
    public function showInlineImage($contents)
    {
        global $injector, $prefs, $registry;

        if (!$prefs->getValue('image_replacement')) {
            return true;
        }

        if (!$contents) {
            return false;
        }

        $from = Horde_Mime_Address::bareAddress($contents->getHeaderOb()->getValue('from'));
        if ($prefs->getValue('image_addrbook') &&
            $registry->hasMethod('contacts/getField')) {
            $params = IMP::getAddressbookSearchParams();
            try {
                if ($registry->call('contacts/getField', array($from, '__key', $params['sources'], false, true))) {
                    return true;
                }
            } catch (Horde_Exception $e) {}
        }

        /* Check admin defined e-mail list. */
        list(, $config) = $injector->getInstance('Horde_Core_Factory_MimeViewer')->getViewerConfig('image/*', 'imp');
        return (!empty($config['safe_addrs']) && in_array($from, $config['safe_addrs']));
    }

}
