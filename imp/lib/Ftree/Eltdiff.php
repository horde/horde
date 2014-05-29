<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Track element changes in the folder tree.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @method void add(string $elt)  Element added to tree.
 * @method void change(string $elt)  Element changed in tree.
 * @method void delete(string $elt)  Element deleted in tree.
 *
 * @property-read array $add  List of added elements.
 * @property-read array $change  List of changed elements.
 * @property-read integer $changed_elts  The number of changed elements
 *                                       tracked.
 * @property-read array $delete  List of deleted elements.
 * @property boolean $track  Is tracking active?
 */
class IMP_Ftree_Eltdiff implements Serializable
{
    /* Constants for $_changes values. */
    const ADD = 1;
    const CHANGE = 2;
    const DELETE = 4;
    const EXIST = 8;

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
                $elt = strval(reset($args));

                /* Don't track base element. */
                if (!strlen($elt)) {
                    return;
                }

                $value = isset($this->_changes[$elt])
                    ? $this->_changes[$elt]
                    : null;

                switch ($name) {
                case 'add':
                    if (is_null($value)) {
                        $value = self::ADD;
                    } elseif ($value & self::EXIST) {
                        $value = self::CHANGE | self::EXIST;
                    } else {
                        $value &= ~self::CHANGE & ~self::DELETE;
                        $value |= self::ADD;
                    }
                    break;

                case 'change':
                    if (is_null($value)) {
                        $value = self::CHANGE | self::EXIST;
                    } elseif (($value & self::EXIST) ||
                              !($value & self::ADD)) {
                        $value &= ~self::ADD & ~self::DELETE;
                        $value |= self::CHANGE;
                    }
                    break;

                case 'delete':
                    if (is_null($value)) {
                        $value = self::DELETE | self::EXIST;
                    } else {
                        $value &= ~self::ADD & ~self::CHANGE;
                        $value |= self::DELETE;
                    }
                    break;
                }

                $this->_changes[$elt] = $value;
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
            switch ($name) {
            case 'add':
                $mask = self::ADD;
                break;

            case 'change':
                $mask = self::CHANGE;
                break;

            case 'delete':
                $mask = self::DELETE;
                break;
            }

            $out = array();
            foreach ($this->_changes as $key => $val) {
                if ($val & $mask) {
                    $out[] = $key;
                }
            }
            return $out;

        case 'changed_elts':
            return count($this->_changes);

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
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            array(
                $this->track,
                $this->_changes
            ),
            array(
                'compression' => false,
                'phpob' => false
            )
        );
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->track,
            $this->_changes
        ) = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

}
