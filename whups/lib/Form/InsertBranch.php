<?php
/**
 * Form for adding a new query branch
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */
class Whups_Form_InsertBranch extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Insert Branch"));

        $branchtypes = array(
            Whups_Query::TYPE_AND => _("And"),
            Whups_Query::TYPE_OR  => _("Or"),
            Whups_Query::TYPE_NOT => _("Not"));

        $this->addHidden(null, 'path', 'text', false, true);
        $this->addVariable(
            _("Branch Type"), 'type', 'enum', true, false, null, array($branchtypes));
    }

}
