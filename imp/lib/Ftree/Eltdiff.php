<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Track element changes in the folder tree.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read array $add
 * @property-read array $change
 * @property-read array $delete
 * @property boolean $track  Is tracking active?
 */
class IMP_Ftree_Eltdiff implements Serializable
{
    /**
     * Has the internal data structure changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * List of diffs.
     *
     * @var array
     */
    protected $_changes = array();

    /**
     * Type map.
     *
     * @var array
     */
    protected $_map = array(
        'add' => 1,
        'change' => 2,
        'delete' => 3
    );

    /**
     * Is tracking active?
     *
     * @var boolean
     */
    protected $_track = false;

    /**
     */
    public function __call($name, $args)
    {
        switch ($name) {
        case 'add':
        case 'change':
        case 'delete':
            if ($this->track) {
                $this->_changes[strval(reset($args))] = $this->_map[$name];
            }
            break;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'add':
        case 'change':
        case 'delete':
            return array_keys($this->_changes, $this->_map[$name]);

        case 'track':
            return $this->_track;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'track':
            $value = (bool)$value;
            if ($value != $this->_track) {
                $this->_track = $value;
                $this->changed = true;
            }
            break;
        }
    }

    /**
     * Clear diff stats.
     */
    public function clear()
    {
        if (!empty($this->_changes)) {
            $this->_changes = array();
            $this->changed = true;
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->track,
            $this->_changes
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->track,
            $this->_changes
        ) = json_decode($data, true);
    }

}
