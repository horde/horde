<?php
/**
 * Horde_Form for editing notepads.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Mnemo
 */
/**
 * The Mnemo_Form_EditNotepadclass provides the form for editing a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Mnemo
 */
class Mnemo_Form_EditNotepad extends Horde_Form
{
    /**
     * Notepad being edited
     */
    protected $_notepad;

    public function __construct(&$vars, $notepad)
    {
        $this->_notepad = &$notepad;
        parent::__construct($vars, sprintf(_("Edit %s"), $notepad->get('name')));

        $this->addHidden('', 'n', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Save")));
    }

    public function execute()
    {
        $this->_notepad->set('name', $this->_vars->get('name'));
        $this->_notepad->set('desc', $this->_vars->get('description'));
        $this->_notepad->save();

        return true;
    }

}
