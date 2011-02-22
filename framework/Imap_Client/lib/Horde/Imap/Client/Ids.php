<?php
/**
 * This object provides a way to identify a list of IMAP indices.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 *
 * @property boolean $all  TODO
 * @property array $ids  TODO
 * @property boolean $search_res  TODO
 * @property boolean $sequence  TODO
 */
class Horde_Imap_Client_Ids implements Countable, Iterator, Serializable
{
    /* Constants. */
    const ALL = "\01";
    const SEARCH_RES = "\02";

    /**
     * List of IDs.
     *
     * @var mixed
     */
    protected $_ids = array();

    /**
     * Are IDs message sequence numbers?
     *
     * @var boolean
     */
    protected $_sequence = false;

    /**
     * Constructor.
     *
     * @param mixed $ids         See self::add().
     * @param boolean $sequence  Are $ids message sequence numbers?
     */
    public function __construct($ids = null, $sequence = false)
    {
        $this->add($ids);
        $this->_sequence = $sequence;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'all':
            return ($this->_ids === self::ALL);

        case 'ids':
            return is_array($this->_ids)
                ? $this->_ids
                : array();

        case 'search_res':
            return ($this->_ids === self::SEARCH_RES);

        case 'sequence':
            return (bool)$this->_sequence;
        }
    }

    /**
     */
    public function __toString()
    {
        $utils = new Horde_Imap_Client_Utils();
        return strval($utils->toSequenceString($this->_ids));
    }

    /**
     * Add IDs to the current object.
     *
     * @param mixed $ids  Either self::ALL, self::SEARCH_RES,
     *                    Horde_Imap_Client_Ids object, array, or string.
     */
    public function add($ids)
    {
        if (!is_null($ids)) {
            $add = array();

            switch ($ids) {
            case self::ALL:
            case self::SEARCH_RES:
                $this->_ids = $ids;
                return;

            case ($ids instanceof Horde_Imap_Client_Ids):
                $add = $ids->ids;
                break;

            case is_array($ids):
                $add = $ids;
                break;

            case is_string($ids):
            case is_integer($ids):
                if (is_numeric($ids)) {
                    $add = array($ids);
                } else {
                    $utils = new Horde_Imap_Client_Utils();
                    $add = $utils->fromSequenceString($ids);
                }
                break;
            }

            $this->_ids = is_array($this->_ids)
                ? array_keys(array_flip(array_merge($this->_ids, $add)))
                : $add;
        }
    }

    /**
     */
    public function reverse()
    {
        if (is_array($this->_ids)) {
            $this->_ids = array_reverse($this->_ids);
        }
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        return is_array($this->_ids)
            ? count($this->_ids)
           : 0;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return is_array($this->_ids)
            ? current($this->_ids)
            : null;
    }

    /**
     */
    public function key()
    {
        return is_array($this->_ids)
            ? key($this->_ids)
            : null;
    }

    /**
     */
    public function next()
    {
        if (is_array($this->_ids)) {
            next($this->_ids);
        }
    }

    /**
     */
    public function rewind()
    {
        if (is_array($this->_ids)) {
            reset($this->_ids);
        }
    }

    /**
     */
    public function valid()
    {
        return !is_null($this->key());
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        $save = array('s' => $this->_sequence);

        switch ($this->_ids) {
        case self::ALL:
            $save['a'] = true;
            break;

        case self::SEARCH_RES:
            $save['sr'] = true;
            break;

        default:
            $save['i'] = strval($this);
            break;
        }

        return serialize($save);
    }

    /**
     */
    public function unserialize($data)
    {
        $save = @unserialize($data);

        $this->_sequence = !empty($save['s']);

        if (isset($save['a'])) {
            $this->_ids = self::ALL;
        } elseif (isset($save['sr'])) {
            $this->_ids = self::SEARCH_RES;
        } elseif (isset($save['i'])) {
            $this->add($save['i']);
        }
    }

}
