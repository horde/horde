<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Special prefs handling for the 'image_replacement_addrs' preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_ImageReplacement implements Horde_Core_Prefs_Ui_Special
{
    /**
     * Safe address list.
     *
     * @var Horde_Mail_Rfc822_List
     */
    protected $_addrlist;

    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Text');

        $view->safe_addrs = implode("\n", $this->safeAddrList()->bare_addresses);

        return $view->render('imagereplacement');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        $alist = new Horde_Mail_Rfc822_List(preg_split("/[\r\n]+/", $ui->vars->safe_addrs));
        $alist->unique();

        if ($GLOBALS['prefs']->setValue('image_replacement_addrs', Horde_Serialize::serialize($alist->bare_addresses, Horde_Serialize::JSON))) {
            $this->_addrlist = $alist;
            return true;
        }

        return false;
    }

    /**
     * @return Horde_Mail_Rfc822_List
     */
    public function safeAddrList()
    {
        if (!isset($this->_addrlist)) {
            $alist = Horde_Serialize::unserialize($GLOBALS['prefs']->getValue('image_replacement_addrs'), Horde_Serialize::JSON);
            if (empty($alist)) {
                $alist = array();
            }

            $this->_addrlist = new Horde_Mail_Rfc822_List($alist);
        }

        return $this->_addrlist;
    }

    /**
     * @param mixed $address  Address to add to the safe address list.
     *
     * @return boolean  True if successfully added.
     */
    public function addSafeAddrList($address)
    {
        $alist = $this->safeAddrList();
        $alist->add($address);
        $alist->unique();

        return $GLOBALS['prefs']->setValue('image_replacement_addrs', Horde_Serialize::serialize($alist->bare_addresses, Horde_Serialize::JSON));
    }

    /**
     * Can addresses be added to the safe list?
     *
     * @return boolean  True if addresses can be added.
     */
    public function canAddToSafeAddrList()
    {
        return !$GLOBALS['prefs']->isLocked('image_replacement_addrs');
    }

}
