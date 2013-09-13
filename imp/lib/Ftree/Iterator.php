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
 * Iterator for the IMP_Ftree object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
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
     * @param mixed $data  Either the parent element (IMP_Ftree_Element
     *                     object), or a flat list of elements to use as
     *                     the level, or null.
     */
    public function __construct($elt)
    {
        global $injector;

        if (is_null($elt)) {
            $elt = $injector->getInstance('IMP_Ftree')->getChildren(IMP_Ftree::BASE_ELT);
        }

        $this->_elts = ($elt instanceof IMP_Ftree_Element)
            ? $elt->child_list
            : $elt;
    }

    /**
     * Return the iterator needed to traverse tree.
     *
     * @return RecursiveIteratorIterator  Iterator.
     */
    public function getIterator()
    {
        return new RecursiveIteratorIterator(
            $this,
            RecursiveIteratorIterator::SELF_FIRST
        );
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
