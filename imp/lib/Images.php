<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code relating to image viewing preferences.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Images
{
    /**
     * Always show inline images?
     *
     * @var boolean
     */
    public $alwaysShow = false;

    /**
     * Results cache.
     *
     * @var array
     */
    protected $_cache = array();

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
        $cid = strval($contents);

        if (!isset($this->_cache[$cid])) {
            $this->_cache[$cid] = $this->_showInlineImage($contents);
        }

        return $this->_cache[$cid];
    }

    /**
     * @see showInlineImage
     */
    protected function _showInlineImage(IMP_Contents $contents)
    {
        global $injector, $prefs;

        if ($this->alwaysShow || !$prefs->getValue('image_replacement')) {
            return true;
        }

        if (!$contents ||
            !($from = $contents->getHeader()->getOb('from'))) {
            return false;
        }

        $res = $injector->getInstance('IMP_Contacts')->searchEmail($from[0]->bare_address, array(
            'email_exact' => true
        ));

        if (count($res)) {
            /* Don't allow personal addresses by default - this is the only
             * e-mail address a Spam sender for sure knows you will recognize
             * so it is too much of a loophole. */
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
