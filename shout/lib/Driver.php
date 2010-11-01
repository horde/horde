<?php
/**
 * Shout_Driver:: defines an API for implementing storage backends for Shout.
 *
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class Shout_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array $_params
     */
    var $_params = array();

    function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
    * Get a list of accounts from the instantiated driver and filter
    * the returned accounts for those which the current user can see/edit
    *
    * @param optional string $filter Filter for types of accounts to return.
    *                                One of "system" "customer" or "all"
    *
    * @param optional string $filterperms Filter accounts for given permissions
    *
    * @return array Accounts valid for this user
    *
    * @access public
    */
    function getAccounts($filters = "all", $filterperms = null)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * For the given account and type, make sure the account has the
     * appropriate properties, that it is effectively of that "type"
     *
     * @param string $account the account to check type for
     *
     * @param string $type the type to verify the account is of
     *
     * @return boolean true of the account is of type, false if not
     *
     * @access public
     */
    function checkAccountType($account, $type)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a list of users valid for the current account.  Return an array
     * indexed by the extension.
     *
     * @param string $account Account for which users should be returned
     *
     * @return array User information indexed by voice mailbox number
     */
    function getUsers($account)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Returns the name of the user's default account
     *
     * @return string User's default account
     */
    function getHomeAccount()
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a account's properties
     *
     * @param string $account Account for which to get properties
     *
     * @return integer Bitfield of properties valid for this account
     */
    function getAccountProperties($account)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a account's extensions and return as a multi-dimensional associative
     * array
     *
     * @param string $account account to return extensions for
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    function getDialplan($account)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Save an extension to the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param string $account Account to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveExtension($account, $extension, $details)
    {
        if (empty($account) || empty($extension)) {
            throw new Shout_Exception(_("Invalid extension."));
        }
        
        if (!Shout::checkRights("shout:accounts:$account:extensions", PERMS_EDIT, 1)) {
            throw new Shout_Exception(_("Permission denied to save extensions in this account."));
        }
    }

    public function deleteExtension($account, $extension)
    {
        if (empty($account) || empty($extension)) {
            throw new Shout_Exception(_("Invalid extension."));
        }

        if (!Shout::checkRights("shout:accounts:$account:extensions",
            PERMS_DELETE, 1)) {
            throw new Shout_Exception(_("Permission denied to delete extensions in this account."));
        }
    }

    /**
     * Save a device to the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param string $account Account to which the user should be added
     *
     * @param string $extension Extension to be saved
     *
     * @param array $details Phone numbers, PIN, options, etc to be saved
     *
     * @return TRUE on success, PEAR::Error object on error
     * @throws Shout_Exception
     */
    public function saveDevice($account, $devid, &$details)
    {
        if (empty($account)) {
            throw new Shout_Exception(_("Invalid device information."));
        }

        if (!Shout::checkRights("shout:accounts:$account:devices", PERMS_EDIT, 1)) {
            throw new Shout_Exception(_("Permission denied to save devices in this account."));
        }

        if (empty($devid) || !empty($details['genauthtok'])) {
            list($devid, $password) = Shout::genDeviceAuth($account);
            $details['devid'] = $devid;
            $details['password'] = $password;
        }


    }

    /**
     * Delete a device from the backend.
     *
     * This method is intended to be overridden by a child class.  However it
     * also implements some basic checks, so a typical backend will still
     * call this method via parent::
     *
     * @param <type> $account
     * @param <type> $devid
     */
    public function deleteDevice($account, $devid)
    {
        if (empty($account) || empty($devid)) {
            throw new Shout_Exception(_("Invalid device."));
        }

        if (!Shout::checkRights("shout:accounts:$account:devices",
            PERMS_DELETE, 1)) {
            throw new Shout_Exception(_("Permission denied to delete devices in this account."));
        }
    }

    /**
     * Attempts to return a concrete Shout_Driver instance based on
     * $driver.
     *
     * @param string $driver  The type of the concrete Shout_Driver subclass
     *                        to return.  The class name is based on the storage
     *                        driver ($driver).  The code is dynamically
     *                        included.
     *
     * @param array  $params  (optional) A hash containing any additional
     *                        configuration or connection parameters a
     *                        subclass might need.
     *
     * @return mixed  The newly created concrete Shout_Driver instance, or
     *                false on an error.
     */
    function &factory($class, $driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf'][$class]['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            if ($GLOBALS['conf'][$class]['params']['driverconfig'] == 'horde') {
                $params = array_merge(Horde::getDriverConfig('storage', $driver),
                                      $GLOBALS['conf'][$class]['params']);
            } else {
                $params = $GLOBALS['conf'][$class]['params'];
            }
        }

        $params['class'] = $class;

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Shout_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

}
