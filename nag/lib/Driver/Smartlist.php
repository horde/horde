<?php
/**
 * Nag storage driver for handling smart tasklists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Nag
 */
class Nag_Driver_Smartlist extends Nag_Driver
{
    /**
     * The composed Nag_Driver
     *
     * @var Nag_Driver
     */
    protected $_driver;

    /**
     * The share object the smartlist is based on.
     *
     * @var array  An array of criteria
     */
    protected $_share;

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $tasklist  The tasklist to load.
     * @param array $params     A hash containing connection parameters.
     */
    public function __construct($tasklist, $params = array())
    {
        $this->_driver = $params['driver'];
        $this->_share = $GLOBALS['nag_shares']->getShare($tasklist);
        $this->_search = unserialize($this->_share->get('search'));
        $this->tasks = new Nag_Task();
    }

    public function add(array $task)
    {
        throw new Nag_Exception(_("Cannot add tasks to smart task lists."));
    }

    /**
     * Needed to satisfy the abstract parent class.
     */
    protected function _add(array $task)
    {
    }

    public function modify($taskId, array $task)
    {
        $this->_driver->modify($taskId, $task);
    }

    public function _modify($taskId, array $task)
    {
    }

    public function delete($taskId)
    {
        $this->_driver->delete($taskId);
    }

    protected function _delete($taskId)
    {
    }

    /**
     * @TODO
     */
    public function deleteAll()
    {

    }

    public function _deleteAll()
    {
    }

    /**
     * Return the list of tasks that match this smart list's search criteria.
     *
     */
    public function retrieve()
    {
        $this->tasks = $this->_search->getSlice();

    }

    public function getChildren($parentId)
    {
        return $this->_driver->getChildren($parentId);
    }

    public function get($taskId)
    {
        return $this->_driver->get($taskId);
    }

    public function getByUID($uid)
    {
        return $this->_driver->getByUID($uid);
    }

}