<?php
/**
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

class Whups_Form_Queue_StepThree extends Horde_Form
{

    public function __construct(&$vars, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'group', 'int', false, true);
        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'newcomment', 'longtext', false, true);

        $info = $whups_driver->getQueue($vars->get('queue'));
        if (!empty($info['versioned'])) {
            $this->addHidden('', 'version', 'int', true, true);
        }

        /* Give user an opportunity to check that state and priority
         * are still valid. */
        $type = $vars->get('type');
        $this->addVariable(_("State"), 'state', 'enum', true, false, null, array($whups_driver->getStates($type)));
        $this->addVariable(_("Priority"), 'priority', 'enum', true, false, null, array($whups_driver->getPriorities($type)));
    }

}