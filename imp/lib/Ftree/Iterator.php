<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator for the IMP_Ftree object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Iterator implements RecursiveIterator
{
    /**
     * Sorted list of elements.
     *
     * @var array
     */
    protected $_elts = array();

    /**
     * Constructor.
     *
     * @param mixed $elt  Either the parent element of the level
     *                    (IMP_Ftree_Element object), or a flat list of
     *                    Ftree elements to use as the base level.
     */
    public function __construct($elt)
    {
        $this->_elts = ($elt instanceof IMP_Ftree_Element)
            ? $elt->child_list
            : $elt;
    }

    /* RecursiveIterator methods. */

    /**
     */
    public function getChildren()
    {
        return new self($this->current());
    }

    /**
     */
    public function hasChildren()
    {
        return $this->current()->children;
    }

    /**
     */
    public function current()
    {
        return current($this->_elts);
    }

    /**
     */
    public function key()
    {
        return key($this->_elts);
    }

    /**
     */
    public function next()
    {
        next($this->_elts);
    }

    /**
     */
    public function rewind()
    {
        reset($this->_elts);
    }

    /**
     */
    public function valid()
    {
        return !is_null($this->key());
    }

}
