<?php
/**
 * Displays and handles the form to change the ticket type.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

class Whups_Form_SetTypeStepOne extends Horde_Form
{
    public function __construct(&$vars, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        /* Types */
        $queue = $vars->get('queue');
        $this->addVariable(_("New Type"), 'type', 'enum', true, false, null, array($whups_driver->getTypes($queue)));
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);

        /* Group restrictions. */
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        $mygroups = $groups->listGroups($GLOBALS['registry']->getAuth());
        if ($mygroups) {
            foreach (array_keys($mygroups) as $gid) {
                $grouplist[$gid] = $groups->getName($gid, true);
            }
            asort($grouplist);
            $grouplist = array_merge(array(0 => _("Any Group")), $grouplist);
            $this->addVariable(_("Viewable only by members of"), 'group', 'enum', true, false, null, array($grouplist));
        }
    }

}