<?php
/**
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

/**
 * Queue editing forms.
 */
class Whups_Form_Queue_StepOne extends Horde_Form
{
    public function __construct(&$vars, $title = '')
    {
        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);

        /* Queues. */
        $this->addVariable(
            _("New Queue"), 'queue', 'enum', true, false, null,
            array(Whups::permissionsFilter($GLOBALS['whups_driver']->getQueues(),
                                           'queue', Horde_Perms::EDIT)));
        $this->addVariable(_("Comment"), 'newcomment', 'longtext', false);

        /* Group restrictions. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => Horde_Perms::EDIT)) ||
            $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('whups:hiddenComments', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $mygroups = $groups->listGroups($GLOBALS['registry']->getAuth());
            if ($mygroups) {
                foreach (array_keys($mygroups) as $gid) {
                    $grouplist[$gid] = $groups->getName($gid, true);
                }
                asort($grouplist);
                $grouplist = array_merge(array(0 => _("Any Group")),
                                         $grouplist);
                $this->addVariable(_("Viewable only by members of"), 'group',
                                   'enum', true, false, null,
                                   array($grouplist));
            }
        }
    }

}