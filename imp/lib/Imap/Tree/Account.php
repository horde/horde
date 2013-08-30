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
 * Abstract class definining an account source for the IMP folder tree.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read IMP_Imap $imp_imap  IMP IMAP object.
 */
abstract class IMP_Imap_Tree_Account implements Serializable
{
    /* Mask constants for getList(). */
    const INIT = 1;
    const UNSUB = 2;

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
    public function __construct($id = IMP_Imap_Tree::BASE_ELT)
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
     */
    public function __get($name)
    {
        switch ($name) {
        case 'imp_imap':
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create($this->_id == IMP_Imap_Tree::BASE_ELT ? null : $this->_id);
        }
    }

    /**
     * Return a list of mailbox to attribute pairs.
     *
     * @param mixed $query  Either an integer mask (INIT and UNSUB constants)
     *                      or an array of search queries.
     *
     * @return array  Array of elements to be added via
     *                IMP_Imap_Tree#_insertElt().
     */
    abstract public function getList($query = null);

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
