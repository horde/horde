<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Decorate the base Horde_History class to allow the username to be reset
 * if the authenticated status changes after the history object has been
 * initialized.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @todo For H6 we can replace type hints for Horde_History with
 *       Horde_Core_History so we can get rid of all the extra methods here
 *       and simply use __call().
 */
class Horde_Core_History extends Horde_History
{
    /**
     * @var Horde_History
     */
    protected $_history;

    /**
     * Const'r
     *
     * @param Horde_History $history  The actual history object.
     */
    public function __construct(Horde_History $history)
    {
        $this->_history = $history;
    }

    /**
     * @see Horde_History::setLogger()
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_history->setLogger($logger);
    }

    /**
     * Set Cache object.
     *
     * @param Horde_Cache $cache  The cache instance.
     */
    public function setCache(Horde_Cache $cache)
    {
        $this->_history->setCache($cache);
    }

    /**
     * @see Horde_History::log()
     *
     * Overridden to ensure we have the current auth username.
     */
    public function log($guid, array $attributes = array(), $replaceAction = false)
    {
        if (empty($attributes['who'])) {
            $attributes['who'] = $GLOBALS['registry']->getAuth()
                ? $GLOBALS['registry']->getAuth()
                : '';
        }
        $this->_history->log($guid, $attributes, $replaceAction);
    }

    /**
     * @see Horde_History::getHistory()
     */
    public function getHistory($guid)
    {
        return $this->_history->getHistory($guid);
    }

    /**
     * @see Horde_History::getByTimestamp()
     */
    public function getByTimestamp($cmp, $ts, array $filters = array(), $parent = null)
    {
        return $this->_history->getByTimestamp($cmp, $ts, $filters, $parent);
    }

    /**
     * @see Horde_History:getByModSeq()
     */
    public function getByModSeq($start, $end, $filters = array(), $parent = null)
    {
        return $this->_history->getByModSeq($start, $end, $filters, $parent);
    }

    /**
     * @see Horde_History::removeByNames()
     */
    public function removeByNames(array $names)
    {
        $this->_history->removeByNames($names);
    }

    /**
     * @see Horde_History::getActionTimestamp()
     */
    public function getActionTimestamp($guid, $action)
    {
        return $this->_history->getActionTimestamp($guid, $action);
    }

    /**
     * @see Horde_History::removeByParent()
     */
    public function removeByParent($parent)
    {
        $this->_history->removeByParent($parent);
    }

    /**
     * @see Horde_History::getActionModSeq()
     */
    public function getActionModSeq($guid, $action)
    {
        return $this->_history->getActionModSeq($guid, $action);
    }

    /**
     * @see Horde_History::getLatestEntry()
     */
    public function getLatestEntry($guid, $use_ts = false)
    {
        return $this->_history->getLatestEntry($guid, $use_ts);
    }

    /**
     * Return the maximum modification sequence. To be overridden in concrete
     * class.
     *
     * @param string $parent  Restrict to entries a specific parent.
     *
     * @return integer  The modseq
     */
    public function getHighestModSeq($parent = null)
    {
        return $this->_history->getHighestModSeq($parent);
    }

    protected function _log(Horde_History_Log $history, array $attributes, $replaceAction = false)
    {
        // NOOP. Here to satisfy the Horde_History API since we must extend the
        // Horde_History class to satisfy any typehints.
    }

    public function _getByTimestamp($cmp, $ts, array $filters = array(), $parent = null)
    {
        // NOOP
    }

    public function _getHistory($guid)
    {
        // NOOP
    }

}
