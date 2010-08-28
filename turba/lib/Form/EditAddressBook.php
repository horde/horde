<?php
/**
 * Horde_Form for editing address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Turba
 */

/**
 * The Turba_Form_EditAddressBook class provides the form for
 * editing an address book.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_Form_EditAddressBook extends Horde_Form
{
    /**
     * Address book being edited
     */
    var $_addressbook;

    public function __construct(&$vars, &$addressbook)
    {
        $this->_addressbook = &$addressbook;
        parent::__construct($vars, sprintf(_("Edit %s"), $addressbook->get('name')));

        $this->addHidden('', 'a', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $this->_addressbook->set('name', $this->_vars->get('name'));
        $this->_addressbook->set('desc', $this->_vars->get('description'));

        try {
            $this->_addressbook->save();
            return true;
        } catch (Turba_Exception $e) {
            return PEAR::raiseError(sprintf(_("Unable to save address book \"%s\": %s"), $id, $e->getMessage()));
        }
    }

}
