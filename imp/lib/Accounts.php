<?php
/**
 * The IMP_Accounts class provides an interface to deal with storing
 * connection details of additional accounts to access within IMP.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Accounts
{
    /**
     * The cached list of accounts.
     *
     * @var array
     */
    protected $_accounts = null;

    /**
     * Save the accounts list to the prefs backend.
     */
    protected function _save()
    {
        $GLOBALS['prefs']->setValue('accounts', json_encode($this->_accounts));
    }

    /**
     * Return the raw list of servers.
     *
     * @return array  An array of server information.
     */
    public function getList()
    {
        $this->_loadList();
        return $this->_accounts;
    }

    /**
     * Loads the flag list from the preferences into the local cache.
     */
    protected function _loadList()
    {
        if (is_null($this->_accounts)) {
            $this->_accounts = json_decode($GLOBALS['prefs']->getValue('accounts'), true);
        }
    }

    /**
     * Retrieve information on a single account.
     *
     * @param integer $label  The label ID.
     *
     * @return array  The configuration array, or false if label not found.
     */
    public function getAccount($label)
    {
        $this->_loadList();

        return isset($this->_accounts[$label])
            ? $this->_accounts[$label]
            : false;
    }

    /**
     * Add an account.
     *
     * @param array $config  The necessary config:
     * <pre>
     * 'label' - (string) The human-readable label for this account.
     * 'port' - (integer) The remote port.
     * 'secure' -(string) Either 'auto', 'no', or 'yes'.
     * 'server' - (string) The server hostspec.
     * 'type' - (string) Either 'imap' or 'pop3'.
     * 'username' - (string) The username to use.
     * </pre>
     *
     * @return integer  The label ID.
     */
    public function addAccount($config)
    {
        $this->_loadList();

        $this->_accounts[] = $config;
        $this->_save();

        end($this->_accounts);
        return key($this->_accounts);
    }

    /**
     * Delete an account from the list.
     *
     * @param integer $label  The label ID.
     *
     * @return boolean  True on success.
     */
    public function deleteAccount($label)
    {
        if ($this->getAccount($label)) {
            unset($this->_accounts[$label]);
            $this->_save();
            return true;
        }

        return false;
    }

}
