<?php
/**
 * Horde_Form for deleting address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Turba
 */

/**
 * The Turba_Form_DeleteAddressbook class provides the form for
 * deleting an address book.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_Form_DeleteAddressBook extends Horde_Form
{
    /**
     * Address book being deleted
     */
    var $_addressbook;

    public function __construct(&$vars, &$addressbook)
    {
        $this->_addressbook = &$addressbook;
        parent::__construct($vars, sprintf(_("Delete %s"), $addressbook->get('name')));

        $this->addHidden('', 'a', 'text', true);
        $this->addVariable(sprintf(_("Really delete the address book \"%s\"? This cannot be undone and all contacts in this address book will be permanently removed."), $this->_addressbook->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    /**
     * @TODO Remove share from 'addressbooks' pref
     */
    function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if (!$GLOBALS['registry']->getAuth() ||
            $this->_addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {
            return PEAR::raiseError(_("You do not have permissions to delete this address book."));
        }

        $driver = &Turba_Driver::singleton($this->_addressbook->getName());
        if (is_a($driver, 'PEAR_Error')) {
            return $driver;
        }

        // We have a Turba_Driver, try to delete the address book.
        $result = $driver->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Address book successfully deleted from backend, remove the
        // share.
        try {
            $GLOBALS['turba_shares']->removeShare($this->_addressbook);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Turba_Exception($e);
        }

        if (isset($_SESSION['turba']['source']) && $_SESSION['turba']['source'] == Horde_Util::getFormData('deleteshare')) {
            unset($_SESSION['turba']['source']);
        }

        $abooks = json_decode($GLOBALS['prefs']->getValue('addressbooks'));
        if (($pos = array_search($this->_addressbook->getName(), $abooks)) !== false) {
            unset($abooks[$pos]);
            $GLOBALS['prefs']->setValue('addressbooks', json_encode($abooks));
        }

        return true;
    }

}
