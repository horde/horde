<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The form to manage spam filters.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Form_Spam extends Ingo_Form_Base
{
    /**
     * The form field for the spam folder.
     *
     * @var Horde_Form_Variable
     */
    public $folder_var;

    public function __construct($vars, $title = '', $name = null, $features = array())
    {
        parent::__construct($vars, $title, $name, $features);

        if ($this->hasFeature('spamlevel'))
        {
            $v = $this->addVariable(_("Spam Level:"), 'level', 'int', true, false, _("Messages with a likely spam score greater than or equal to this number will be treated as spam."));
            $v->setHelp('spam-level');
        }

        if ($this->hasFeature('spamfolder'))
        {
            $this->folder_var = $this->addVariable(_("Folder to receive spam:"), 'folder', 'ingo_folders', true);
            $this->folder_var->setHelp('spam-folder');
            $this->addHidden('', 'actionID', 'text', false);
            $this->addHidden('', 'folder_new', 'text', false);
        }

        $this->setButtons(_("Save"));
    }

    public function renderActive(
        $renderer = null, $vars = null, $action = '', $method = 'get',
        $enctype = null, $focus = true
    )
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        $vars = clone $vars;
        unset($vars->folder_new);

        parent::renderActive(
            $renderer, $vars, $action, $method, $enctype, $focus
        );
    }

    public function validate($vars = null, $canAutoFill = false)
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }
        if (strlen($vars->get('folder_new'))) {
            $this->folder_var->type->newFolderSet = true;
        }
        return parent::validate($vars, $canAutoFill);
    }

}
