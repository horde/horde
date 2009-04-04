<?php
/**
 * Horde_Form for creating ledgers.
 *
 * $Horde: fima/lib/Forms/CreateLedger.php,v 1.0 2008/06/19 18:12:08 trt Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Variables */
require_once 'Horde/Variables.php';

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Fima_CreateLedgerForm class provides the form for
 * creating a ledger.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_CreateLedgerForm extends Horde_Form {

    function Fima_CreateLedgerForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Ledger"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        // Create new share.
        $ledger = $GLOBALS['fima_shares']->newShare(md5(microtime()));
        if (is_a($ledger, 'PEAR_Error')) {
            return $ledger;
        }
        $ledger->set('name', $this->_vars->get('name'));
        $ledger->set('desc', $this->_vars->get('description'));
        return $GLOBALS['fima_shares']->addShare($ledger);
    }

}
