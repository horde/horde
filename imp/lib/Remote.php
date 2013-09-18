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
    /* The mailbox remote prefix. */
    const MBOX_PREFIX = "remotembox\0";

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

    /**
     * Is the given mailbox a remote mailbox?
     *
     * @param string $id  The mailbox name/identifier.
     *
     * @return boolean  Whether the given mailbox name is a remote mailbox.
     */
    public function isRemoteMbox($id)
    {
        return (strpos($id, self::MBOX_PREFIX) === 0);
    }

    /**
     * Return the remote account for a valid remote mailbox/identifier.
     *
     * @param string $id  The mailbox name/identifier.
     *
     * @return mixed  Either a IMP_Remote_Account object or null.
     */
    public function getRemoteById($id)
    {
        return ($this->isRemoteMbox($id) && (count($parts = explode("\0", $id)) > 1))
            ? $this[implode("\0", array_slice($parts, 0, 2))]
            : null;
    }

    /**
     * Return the IMAP mailbox name for the given remote mailbox identifier.
     *
     * @param string $id  The mailbox name/identifier.
     *
     * @return string  The IMAP mailbox name.
     */
    public function getMailboxById($id)
    {
        return ($account = $this->getRemoteById($id))
            ? substr($id, strlen($account) + 1)
            : '';
    }

    /**
     * Return the label for the given mailbox.
     *
     * @param string $id  The mailbox name/identifier.
     *
     * @return string  The mailbox label.
     */
    public function label($id)
    {
        return isset($this[$id])
            ? $this[$id]->label
            : strval($this->getMailboxById($id));
    }

    /**
     * Strip the identifying label from a mailbox ID.
     *
     * @param string $id  The mailbox query ID.
     *
     * @return string  The remote ID, with any IMP specific identifying
                       information stripped off.
     */
    protected function _strip($id)
    {
        return $this->isRemoteMbox($id)
            ? substr($id, strlen(self::MBOX_PREFIX))
            : strval($id);
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
        return isset($this->_accounts[$this->_strip($offset)]);
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
        $offset = $this->_strip($offset);

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
        $this->_accounts[$this->_strip($offset)] = $value;
        $this->_save();
    }

    /**
     * Delete a remote account.
     *
     * @param string $offset  Account ID.
     */
    public function offsetUnset($offset)
    {
        $offset = $this->_strip($offset);

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
