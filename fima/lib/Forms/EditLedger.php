<?php
/**
 * Horde_Form for editing ledgers.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Fima_EditLedgerForm class provides the form for
 * editing a ledger.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_EditLedgerForm extends Horde_Form {

    /**
     * Ledger being edited
     */
    var $_ledger;

    function Fima_EditLedgerForm(&$vars, &$ledger)
    {
        $this->_ledger = &$ledger;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $ledger->get('name')));

        $this->addHidden('', 'l', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $this->_ledger->set('name', $this->_vars->get('name'));
        $this->_ledger->set('desc', $this->_vars->get('description'));
        $result = $this->_ledger->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save ledger \"%s\": %s"), $id, $result->getMessage()));
        }
        return true;
    }

}
