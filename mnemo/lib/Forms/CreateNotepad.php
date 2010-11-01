<?php
/**
 * Horde_Form for creating notepads.
 *
 * $Horde: mnemo/lib/Forms/CreateNotepad.php,v 1.2 2009/06/10 17:33:40 slusarz Exp $
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Mnemo
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Mnemo_CreateNotepadForm class provides the form for
 * creating a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Mnemo 2.2
 * @package Mnemo
 */
class Mnemo_CreateNotepadForm extends Horde_Form {

    function Mnemo_CreateNotepadForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Notepad"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        // Create new share.
        try {
            $notepad = $GLOBALS['mnemo_shares']->newShare(strval(new Horde_Support_Uuid()));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Mnemo_Exception($e);
        }
        $notepad->set('name', $this->_vars->get('name'));
        $notepad->set('desc', $this->_vars->get('description'));
        return $GLOBALS['mnemo_shares']->addShare($notepad);
    }

}
