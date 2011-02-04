<?php
/**
 * Horde_Form for creating notepads.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Mnemo
 */
/**
 * The Mnemo_Form_CreateNotepad class provides the form for creating a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Mnemo
 */
class Mnemo_Form_CreateNotepad extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Create Notepad"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Create")));
    }

    public function execute()
    {
        // Create new share.
        try {
            $notepad = $GLOBALS['mnemo_shares']->newShare($GLOBALS['registry']->getAuth(), strval(new Horde_Support_Uuid()));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Mnemo_Exception($e);
        }
        $notepad->set('name', $this->_vars->get('name'));
        $notepad->set('desc', $this->_vars->get('description'));
        return $GLOBALS['mnemo_shares']->addShare($notepad);
    }

}
