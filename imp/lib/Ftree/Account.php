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
 * Abstract class definining an account source for the IMP folder tree.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Ftree_Account implements Serializable
{
    /* Mask constants for getList(). */
    const INIT = 1;
    const UNSUB = 2;

    /* Mask constants for delete(). */
    const DELETE_ELEMENT = 1;
    const DELETE_ELEMENT_QUICK = 2;
    const DELETE_RECURSIVE = 4;

    /**
     * Account ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * Constructor.
     *
     * @param string $id  Account ID.
     */
    public function __construct($id = IMP_Ftree::BASE_ELT)
    {
        $this->_id = strval($id);
    }

    /**
     * @return string  Account ID.
     */
    public function __toString()
    {
        return $this->_id;
    }

    /**
     * Return a list of mailbox to attribute pairs.
     *
     * @param array $query  Array of search queries.
     * @param mixed $mask   Integer mask (INIT and UNSUB constants).
     *
     * @return array  Array of elements to be added via
     *                IMP_Ftree#_insertElt().
     */
    abstract public function getList($query = array(), $mask = 0);

    /**
     * Return the mailbox selction to delete.
     *
     * @param IMP_Ftree_Element $elt  Element to delete.
     *
     * @return integer  Mask of mailboxes to delete.
     */
    abstract public function delete(IMP_Ftree_Element $elt);

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $this->_id;
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_id = $data;
    }

}
