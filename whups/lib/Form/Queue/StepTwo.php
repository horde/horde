<?php
/**
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

class Whups_Form_Queue_StepTwo extends Horde_Form
{
    public function __construct(&$vars, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'group', 'int', false, true);
        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'newcomment', 'longtext', false, true);

        /* Give the user an opportunity to check that type, version,
         * etc. are still valid. */

        $queue = $vars->get('queue');

        $info = $whups_driver->getQueue($queue);
        if (!empty($info['versioned'])) {
            $versions = $whups_driver->getVersions($vars->get('queue'));
            if (count($versions) == 0) {
                $vtype = 'invalid';
                $v_params = array(_("This queue requires that you specify a version, but there are no versions associated with it. Until versions are created for this queue, you will not be able to create tickets."));
            } else {
                $vtype = 'enum';
                $v_params = array($versions);
            }
            $this->addVariable(_("Queue Version"), 'version', $vtype, true, false, null, $v_params);
        }

        $this->addVariable(_("Type"), 'type', 'enum', true, false, null, array($whups_driver->getTypes($queue)));
    }

}