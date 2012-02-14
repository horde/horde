<?php
/**
 * The form to manage spam filters.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Form_Spam extends Ingo_Form_Base
{
    /**
     * The form field for the spam folder.
     *
     * @var Horde_Form_Variable
     */
    public $folder_var;

    public function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

        $v = $this->addVariable(_("Spam Level:"), 'level', 'int', false, false, _("Messages with a likely spam score greater than or equal to this number will be treated as spam."));
        $v->setHelp('spam-level');

        $this->folder_var = $this->addVariable(_("Folder to receive spam:"), 'folder', 'ingo_folders', false);
        $this->folder_var->setHelp('spam-folder');
        $this->addHidden('', 'actionID', 'text', false);

        $this->setButtons(_("Save"));
    }
}
