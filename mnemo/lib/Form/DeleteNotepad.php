<?php
/**
 * Horde_Form for deleting notepads.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Mnemo
 */
/**
 * The Mnemo_Form_DeleteNotepad class provides the form for deleting a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Mnemo
 */
class Mnemo_Form_DeleteNotepad extends Horde_Form
{
    /**
     * Notepad being deleted
     */
    protected $_notepad;

    /**
     * Const'r
     */
    public function __construct(&$vars, $notepad)
    {
        $this->_notepad = $notepad;
        parent::__construct($vars, sprintf(_("Delete %s"), $notepad->get('name')));

        $this->addHidden('', 'n', 'text', true);
        $this->addVariable(sprintf(_("Really delete the notepad \"%s\"? This cannot be undone and all data on this notepad will be permanently removed."), $this->_notepad->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    public function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if (!$GLOBALS['registry']->getAuth() ||
            $this->_notepad->get('owner') != $GLOBALS['registry']->getAuth()) {

            throw new Horde_Exception_PermissionDenied(_("Permission denied"));
        }

        // Delete the notepad.
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($this->_notepad->getName());
        $result = $storage->deleteAll();
        // Remove share and all groups/permissions.
        try {
            $GLOBALS['mnemo_shares']->removeShare($this->_notepad);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Mnemo_Exception($e->getMessage());
        }

        return true;
    }

}
