<?php
/**
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

class Whups_Form_SetTypeStepTwo extends Horde_Form
{
    public function __construct(&$vars, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, false);
        $this->addHidden('', 'group', 'int', false, false);
        $this->addHidden('', 'type', 'int', true, false);
        $this->addHidden('', 'newcomment', 'longtext', false, false);

        /* Give user an opportunity to check that state and priority
         * are still valid. */
        $type = $vars->get('type');
        $this->addVariable(_("State"), 'state', 'enum', true, false, null, array($whups_driver->getStates($type)));
        $this->addVariable(_("Priority"), 'priority', 'enum', true, false, null, array($whups_driver->getPriorities($type)));
    }

}