<?php
/**
 * Kronolith_Calendar_External_Tasks defines an API for single task lists.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_External_Tasks extends Kronolith_Calendar_External
{
    /**
     * The share of this task list.
     *
     * @var Horde_Share_Object
     */
    protected $_share;

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        $owner = $GLOBALS['registry']->getAuth() &&
            $this->_share->get('owner') == $GLOBALS['registry']->getAuth();

        $hash = parent::toHash();
        $hash['name']  = $this->_share->get('name')
          . ($owner || !$this->_share->get('owner') ? '' : ' [' . $GLOBALS['registry']->convertUsername($this->_share->get('owner'), false) . ']');
        $hash['desc']  = $this->_share->get('desc');
        $hash['owner'] = $owner;
        $hash['fg']    = Kronolith::foregroundColor($this->_share);
        $hash['bg']    = Kronolith::backgroundColor($this->_share);
        $hash['show']  = in_array('tasks/' . $this->_share->getName(), $GLOBALS['display_external_calendars']);
        $hash['edit']  = $this->_share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        $hash['sub']   = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . ($GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? '/rpc/nag/' : '/rpc.php/nag/'), true, -1)
            . ($this->_share->get('owner') ? $this->_share->get('owner') : '-system-') . '/'
            . $this->_share->getName() . '.ics';
        if ($owner) {
            $hash['perms'] = Kronolith::permissionToJson($this->_share->getPermission());
        }

        return $hash;
    }
}
