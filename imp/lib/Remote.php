<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Interface to deal with storing connection details of remote accounts.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Remote implements ArrayAccess, IteratorAggregate
{
    /* Prefix to indicate mailbox is located on remote server. */
    const PREFIX = 'remote\0';

    /**
     * The list of remote accounts.
     *
     * @var array
     */
    protected $_accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_accounts = @unserialize($GLOBALS['prefs']->getValue('remote')) ?: array();
    }

    /**
     * Save the remote accounts list to the prefs backend.
     */
    protected function _save()
    {
        $GLOBALS['prefs']->setValue('remote', serialize($this->_accounts));
    }

    /* ArrayAccess methods. */

    /**
     * Does the account ID exist?
     *
     * @param string $offset  Account ID.
     *
     * @return boolean  True if the account ID exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->_accounts[strval($offset)]);
    }

    /**
     * Retrieve information on a single remote account.
     *
     * @param string $offset  Account ID.
     *
     * @return array  The configuration array, or false if ID not found.
     */
    public function offsetGet($offset)
    {
        $offset = strval($offset);

        return isset($this->_accounts[$offset])
            ? $this->_accounts[$offset]
            : false;
    }

    /**
     * Add a remote account.
     *
     * @param string $offset          Account ID.
     * @param IMP_Remote_Account $ob  Account object.
     */
    public function offsetSet($offset, $value)
    {
        $this->_accounts[strval($offset)] = $value;
        $this->_save();
    }

    /**
     * Delete a remote account.
     *
     * @param string $offset  Account ID.
     */
    public function offsetUnset($offset)
    {
        $offset = strval($offset);

        if (isset($this->_accounts[$offset])) {
            unset($this->_accounts[$offset]);
            $this->_save();
        }
    }

    /* IteratorAggregate method. */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_accounts);
    }

}
