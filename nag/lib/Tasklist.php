<?php
/**
 * Nag_Tasklist is a light wrapper around a Nag tasklist.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Nag
 */
class Nag_Tasklist
{
    protected $_share;

    /**
     * Const'r
     *
     * @param Horde_Share_Object_Base  The base share for this tasklist.
     */
    public function __construct(Horde_Share_Object_Base $share)
    {
        $this->_share = $share;
    }

    /**
     * Convert this tasklist to a hash.
     *
     * @return array  A hash of tasklist properties.
     */
    public function toHash()
    {
        $tasks = Nag::listTasks(array('tasklists' => $this->_share->getName()));
        $hash = array(
            'name' => $this->_share->get('name'),
            'desc' => $this->_share->get('desc'),
            'color' => $this->_share->get('color'),
            'owner' => $this->_share->get('owner'),
            'id' => $this->_share->getName(),
            'count' => $tasks->count(),
            'smart' => $this->_share->get('issmart') ? true : false,
            'overdue' => $tasks->childrenOverdue());

        return $hash;
    }

}