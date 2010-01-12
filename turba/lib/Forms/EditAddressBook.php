<?php
/**
 * Horde_Form for editing address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Turba
 */

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Turba_EditAddressBookForm class provides the form for
 * editing an address book.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_EditAddressBookForm extends Horde_Form {

    /**
     * Address book being edited
     */
    var $_addressbook;

    function Turba_EditAddressBookForm(&$vars, &$addressbook)
    {
        $this->_addressbook = &$addressbook;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $addressbook->get('name')));

        $this->addHidden('', 'a', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $this->_addressbook->set('name', $this->_vars->get('name'));
        $this->_addressbook->set('desc', $this->_vars->get('description'));
        $result = $this->_addressbook->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save address book \"%s\": %s"), $id, $result->getMessage()));
        }
        return true;
    }

}
