<?php
/**
 * The IMP_Fetchmail_Account:: class provides an interface to accessing
 * fetchmail preferences for all mail accounts a user might have.
 *
 * @author  Nuno Loureiro <nuno@co.sapo.pt>
 * @package IMP
 */
class IMP_Fetchmail_Account
{
    /**
     * Array containing all the user's accounts.
     *
     * @var array
     */
    protected $_accounts = array();

    /**
     * Constructor.
     */
    function __construct()
    {
        /* Read all the user's accounts from the prefs object or build
         * a new account from the standard values given in prefs.php. */
        $accounts = @unserialize($GLOBALS['prefs']->getValue('fm_accounts'));
        if (is_array($accounts)) {
            $this->_accounts = $accounts;
        }
    }

    /**
     * Return the number of accounts.
     *
     * @return integer  Number of active accounts.
     */
    public function count()
    {
        return count($this->_accounts);
    }

    /**
     * Saves all accounts in the prefs backend.
     */
    protected function _save()
    {
        $GLOBALS['prefs']->setValue('fm_accounts', serialize($this->_accounts));
    }

    /**
     * Adds a new empty account to the array of accounts.
     *
     * @return integer  The pointer to the created account.
     */
    public function add()
    {
        $this->_accounts[] = array();
        $this->_save();
        return count($this->_accounts) - 1;
    }

    /**
     * Remove an account from the array of accounts
     *
     * @param integer $account  The pointer to the account to be removed.
     *
     * @return array  The removed account.
     */
    public function delete($account)
    {
        $deleted = $this->_accounts[$account];
        unset($this->_accounts[$account]);
        $this->_accounts = array_values($this->_accounts);
        $this->_save();
        return $deleted;
    }

    /**
     * Returns a property from one of the accounts.
     *
     * @param string $key       The property to retrieve.
     * @param integer $account  The account to retrieve the property from.
     *
     * @return mixed  The value of the property or false if the property
     *                doesn't exist.
     */
    public function getValue($key, $account)
    {
        return (isset($this->_accounts[$account][$key])) ? $this->_accounts[$account][$key] : false;
    }

    /**
     * Returns all properties from the requested accounts.
     *
     * @param integer $account  The account to retrieve the properties from.
     *
     * @return array  The entire properties array, or false on error.
     */
    public function getAllValues($account)
    {
        return (isset($this->_accounts[$account])) ? $this->_accounts[$account] : false;
    }

    /**
     * Returns an array with the specified property from all existing accounts.
     *
     * @param string $key  The property to retrieve.
     *
     * @return array  The array with the values from all accounts.
     */
    public function getAll($key)
    {
        $list = array();
        foreach (array_keys($this->_accounts) as $account) {
            $list[$account] = $this->getValue($key, $account);
        }

        return $list;
    }

    /**
     * Sets a property with a specified value.
     *
     * @param string $key       The property to set.
     * @param mixed $val        The value the property should be set to.
     * @param integer $account  The account to set the property in.
     */
    public function setValue($key, $val, $account)
    {
        /* These parameters are checkbox items - make sure they are stored
         * as boolean values. */
        $list = array('del', 'onlynew', 'markseen', 'loginfetch');
        if (in_array($key, $list) && !is_bool($val)) {
            if (($val == 'yes') || (intval($val) != 0)) {
                $val = true;
            } else {
                $val = false;
            }
        }

        $this->_accounts[$account][$key] = $val;
        $this->_save();
    }

    /**
     * Returns true if the pair key/value is already in the accounts array.
     *
     * @param string $key  The account key to search.
     * @param string $val  The value to search for in $key.
     *
     * @return boolean  True if the value was found in $key.
     */
    public function hasValue($key, $val)
    {
        $list = $this->getAll($key);
        foreach ($list as $val2) {
            if (strpos(String::lower($val), String::lower($val2)) !== false) {
                return true;
            }
        }
        return false;
    }
}
