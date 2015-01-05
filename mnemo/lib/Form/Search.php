<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Mnemo
 */

/**
 * Notes search form.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Mnemo
 */
class Mnemo_Form_Search extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars, _("Search"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addVariable(_("For"), 'search_pattern', 'text', false);
        $v = $this->addVariable(
            _("In"), 'search_type', 'radio', false, false, null,
            array(array('desc' => _("Title"), 'body' => _("Body")))
        );
        $v->setDefault('desc');
        $this->setButtons(_("Search"));
    }

    public function render()
    {
        $this->_vars->actionID = 'search_memos';
        parent::renderActive(null, null, Horde::url('list.php'));
    }
}
