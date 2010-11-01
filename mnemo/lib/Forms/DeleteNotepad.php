<?php
/**
 * Horde_Form for deleting notepads.
 *
 * $Horde: mnemo/lib/Forms/DeleteNotepad.php,v 1.7 2009/12/03 00:01:11 jan Exp $
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
 * The Mnemo_DeleteNotepadForm class provides the form for
 * deleting a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Mnemo 2.2
 * @package Mnemo
 */
class Mnemo_DeleteNotepadForm extends Horde_Form {

    /**
     * Notepad being deleted
     */
    var $_notepad;

    function Mnemo_DeleteNotepadForm(&$vars, &$notepad)
    {
        $this->_notepad = &$notepad;
        parent::Horde_Form($vars, sprintf(_("Delete %s"), $notepad->get('name')));

        $this->addHidden('', 'n', 'text', true);
        $this->addVariable(sprintf(_("Really delete the notepad \"%s\"? This cannot be undone and all data on this notepad will be permanently removed."), $this->_notepad->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if (!$GLOBALS['registry']->getAuth() ||
            $this->_notepad->get('owner') != $GLOBALS['registry']->getAuth()) {
            return PEAR::raiseError(_("Permission denied"));
        }

        // Delete the notepad.
        $storage = &Mnemo_Driver::singleton($this->_notepad->getName());
        $result = $storage->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to delete \"%s\": %s"), $this->_notepad->get('name'), $result->getMessage()));
        } else {
            // Remove share and all groups/permissions.
            try {
                $GLOBALS['mnemo_shares']->removeShare($this->_notepad);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                return;
            }
        }

        // Make sure we still own at least one notepad.
        if (count(Mnemo::listNotepads(true)) == 0) {
            // If the default share doesn't exist then create it.
            if (!$GLOBALS['mnemo_shares']->exists($GLOBALS['registry']->getAuth())) {

                $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
                $name = $identity->getValue('fullname');
                if (trim($name) == '') {
                    $name = $GLOBALS['registry']->getAuth();
                }
                try {
                    $notepad = $GLOBALS['mnemo_shares']->newShare($GLOBALS['registry']->getAuth());
                } catch (Horde_Share_Exception $e) {
                    return;
                }
                $notepad->set('name', sprintf(_("%s's Notepad"), $name));
                $GLOBALS['mnemo_shares']->addShare($notepad);
            }
        }

        return true;
    }

}
