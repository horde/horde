<?php
/**
 * Horde_Form for deleting address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
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
    protected $_addressbook;

    public function __construct($vars, $addressbook)
    {
        $this->_addressbook = $addressbook;
        parent::__construct($vars, sprintf(_("Delete %s"), $addressbook->get('name')));

        $this->addHidden('', 'a', 'text', true);
        $this->addVariable(sprintf(_("Really delete the address book \"%s\"? This cannot be undone and all contacts in this address book will be permanently removed."), $this->_addressbook->get('name')), 'desc', 'description', false);

        $this->setButtons(array(
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel")),
        ));
    }

    /**
     * @throws Turba_Exception
     */
    public function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            Horde::url('', true)->redirect();
        }

        if (!$GLOBALS['registry']->getAuth() ||
            $this->_addressbook->get('owner') != $GLOBALS['registry']->getAuth()) {
            throw new Turba_Exception(_("You do not have permissions to delete this address book."));
        }

        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Factory_Driver')
            ->create($this->_addressbook->getName());
        if ($driver->hasCapability('delete_all')) {
            try {
                $driver->deleteAll();
            } catch (Turba_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw $e;
            }
        }

        // Address book successfully deleted from backend, remove the share.
        try {
            $GLOBALS['injector']
                ->getInstance('Turba_Shares')
                ->removeShare($this->_addressbook);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Turba_Exception($e);
        }

        if ($GLOBALS['session']->get('turba', 'source') == Horde_Util::getFormData('deleteshare')) {
            $GLOBALS['session']->remove('turba', 'source');
        }
    }
}
