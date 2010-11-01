<?php
/**
 * Horde_Form for editing notepads.
 *
 * $Horde: mnemo/lib/Forms/EditNotepad.php,v 1.3 2009/06/10 17:33:40 slusarz Exp $
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
 * The Mnemo_EditNotepadForm class provides the form for
 * editing a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Mnemo 2.2
 * @package Mnemo
 */
class Mnemo_EditNotepadForm extends Horde_Form {

    /**
     * Notepad being edited
     */
    var $_notepad;

    function Mnemo_EditNotepadForm(&$vars, &$notepad)
    {
        $this->_notepad = &$notepad;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $notepad->get('name')));

        $this->addHidden('', 'n', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $this->_notepad->set('name', $this->_vars->get('name'));
        $this->_notepad->set('desc', $this->_vars->get('description'));
        $result = $this->_notepad->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save notepad \"%s\": %s"), $id, $result->getMessage()));
        }
        return true;
    }

}
