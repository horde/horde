<?php
/**
 * Fima_Driver:: defines an API for implementing storage backends for
 * Fima.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Driver {

    /**
     * Array holding the current accounts. Each array entry is a hash
     * describing an account. The array is indexed by accountId.
     *
     * @var array
     */
    var $_accounts = array();

    /**
     * Array holding the current postings. Each array entry is a hash
     * describing a posting. The array is indexed by postingId.
     *
     * @var array
     */
    var $_postings = array();

    /**
     * Integer containing the current total count of postings.
     *
     * @var integer
     */
    var $_postingsCount = 0;

    /**
     * Amount containing the current total result of postings.
     *
     * @var float
     */
    var $_postingsResult = 0;

    /**
     * String containing the current ledger.
     *
     * @var string
     */
    var $_ledger = '';

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructor - just store the $params in our newly-created
     * object. All other work is done by initialize().
     *
     * @param array $params  Any parameters needed for this driver.
     */
    function Fima_Driver($params = array(), $errormsg = null)
    {
        $this->_params = $params;
        if (is_null($errormsg)) {
            $this->_errormsg = _("The Finances backend is not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * Returns the current driver's additional parameters.
     *
     * @return array  Hash containing the driver's additional parameters.
     */
    function getParams()
    {
        return $this->_params;
    }

    /**
     * Lists accounts based on the given criteria. All accounts will be
     * returned by default.
     *
     * @return array  Returns a list of the requested accounts.
     */
    function listAccounts()
    {
        return $this->_accounts;
    }

    /**
     * Lists postings based on the given criteria. All postings will be
     * returned by default.
     *
     * @return array  Returns a list of the requested postings.
     */
    function listPostings()
    {
        return $this->_postings;
    }

    /**
     * Adds an account.
     *
     * @param string $number     The number of the account.
     * @param string $type       The type of the account.
     * @param string $name       The name (short) of the account.
     * @param boolean $eo	     Extraordinary account.
     * @param string $desc       The description (long) of the account.
     * @param boolean $closed    Close account.
     *
     * @return mixed             ID of the new account or PEAR_Error
     */
    function addAccount($number, $type, $name, $eo, $desc, $closed)
    {
        $accountId = $this->_addAccount($number, $type, $name, $eo, $desc, $closed);
        if (is_a($accountId, 'PEAR_Error')) {
            return $accountId;
        }

        /* Log the creation of this item in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $accountId, array('action' => 'add'), true);

        return $accountId;
    }

    /**
     * Modifies an existing account.
     *
     * @param string $accountId  The account to modify.
     * @param string $number     The number of the account.
     * @param string $type       The type of the account.
     * @param string $name       The name (short) of the task.
     * @param boolean $eo	     Extraordinary account.
     * @param string $desc       The description (long) of the task.
     * @param boolean $closed    Close account.
     *
     * return mixed              True or PEAR_Error
     */
    function modifyAccount($accountId, $number, $type, $name, $eo, $desc, $closed)
    {
        $modify = $this->_modifyAccount($accountId, $number, $type, $name, $eo, $desc, $closed);
        if (is_a($modify, 'PEAR_Error')) {
            return $modify;
        }

        /* Log the modification of this item in the history log. */
        $account = $this->getAccount($accountId);
        if (!is_a($account, 'PEAR_Error')) {
            $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $account['account_id'], array('action' => 'modify'), true);
        }

        return true;
    }

    /**
     * Deletes an account and deletes/shifts of subaccounts and postings.
     *
     * @param string $accountId     The account to delete.
     * @param mixed $dsSubaccounts  True/false when deleting subaccounts,
     * 								accountId when shifting subaccounts
     * @param mixed $dsPostings     True/false when deleting postings,
     * 								accountId when shifting postings
     *
     * @return mixed                True or PEAR_Error
     */
    function deleteAccount($accountId, $dsSubaccounts = false, $dsPostings = true)
    {
        /* Get the account's details for use later. */
        $account = $this->getAccount($accountId);

        $delete = $this->_deleteAccount($accountId, $dsSubaccounts, $dsPostings);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Log the deletion of this item in the history log. */
        if (!is_a($account, 'PEAR_Error')) {
            $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $account['account_id'], array('action' => 'delete'), true);
        }

        return true;
    }

    /**
     * Adds a posting.
     *
     * @param string $type     The posting type.
     * @param integer $date    The posting date.
     * @param string $asset    The ID of the asset account.
     * @param string $account  The ID of the account.
     * @param boolean $eo	   Extraordinary posting.
     * @param float $amount    The posting amount.
     * @param string $desc     The posting description.
     *
     * @return mixed           ID of the new posting or PEAR_Error
     */
    function addPosting($type, $date, $asset, $account, $eo, $amount, $desc)
    {
        $postingId = $this->_addPosting($type, $date, $asset, $account, $eo, $amount, $desc);
        if (is_a($postingId, 'PEAR_Error')) {
            return $postingId;
        }

        /* Log the creation of this item in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $postingId, array('action' => 'add'), true);

        return $postingId;
    }

    /**
     * Modifies an existing posting.
     *
     * @param string $postingId  The posting to modify.
     * @param string $type       The posting type.
     * @param integer $date      The posting date.
     * @param string $asset      The ID of the asset account.
     * @param string $account    The ID of the account.
     * @param boolean $eo	     Extraordinary posting.
     * @param float $amount      The posting amount.
     * @param string $desc       The posting description.
     *
     * @return mixed             True or PEAR_Error
     */
    function modifyPosting($postingId, $type, $date, $asset, $account, $eo, $amount, $desc)
    {
        $modify = $this->_modifyPosting($postingId, $type, $date, $asset, $account, $eo, $amount, $desc);
        if (is_a($modify, 'PEAR_Error')) {
            return $modify;
        }

        /* Log the modification of this item in the history log. */
        $posting = $this->getPosting($postingId);
        if (!is_a($posting, 'PEAR_Error')) {
            $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $posting['posting_id'], array('action' => 'modify'), true);
        }

        return true;
    }

    /**
     * Deletes a posting.
     *
     * @param string $postingId     The posting to delete.
     *
     * @return mixed                True or PEAR_Error
     */
    function deletePosting($postingId)
    {
        /* Get the posting's details for use later. */
        $posting = $this->getPosting($postingId);

        $delete = $this->_deletePosting($postingId);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Log the deletion of this item in the history log. */
        if (!is_a($posting, 'PEAR_Error')) {
            $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $posting['posting_id'], array('action' => 'delete'), true);
        }

        return true;
    }

    /**
     * Shifts a posting.
     *
     * @param string $postingId  The posting to shift.
     * @param string $type		 The posting type shifting to.
     * @param string $asset      The ID of the asset account.
     * @param string $account    The ID of the account.
     *
     * @return mixed             True or PEAR_Error
     */
    function shiftPosting($postingId, $type, $asset, $account)
    {
        /* Get the posting's details for use later. */
        $posting = $this->getPosting($postingId);
        $shift = $this->_shiftPosting($postingId, $type, $asset, $account);
        if (is_a($shift, 'PEAR_Error')) {
            return $shift;
        }

        /* Log the shifting of this item in the history log. */
        if (!is_a($posting, 'PEAR_Error')) {
            $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':' . $posting['posting_id'], array('action' => 'shift'), true);
        }

        return true;
    }

    /**
     * Deletes all postings and accounts.
     *
     * @param mixed $accounts  boolean or account_type
     * @param mixed $accounts  boolean or posting_type.
     *
     * @return mixed             True or PEAR_Error
     */
    function deleteAll($accounts = true, $postings = true)
    {
        $delete = $this->_deleteAll($accounts, $postings);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Log the deletion of this item in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log('fima:' . $this->_ledger . ':all', array('action' => 'delete'), true);

        return true;
    }

    /**
     * Attempts to return a concrete Fima_Driver instance based on $driver.
     *
     * @param string    $ledger     The name of the ledger to load.
     *
     * @param string    $driver     The type of the concrete Fima_Driver subclass
     *                              to return.  The class name is based on the
     *                              storage driver ($driver).  The code is
     *                              dynamically included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Fima_Driver instance, or
     *					false on an error.
     */
    function &factory($ledger = '', $driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Fima_Driver_' . $driver;
        if (class_exists($class)) {
            $fima = new $class($ledger, $params);
            $result = $fima->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $fima = new Fima_Driver($params, sprintf(_("The Finances backend is not currently available: %s"), $result->getMessage()));
            }
        } else {
            $fima = new Fima_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $fima;
    }

    /**
     * Attempts to return a reference to a concrete Fima_Driver
     * instance based on $driver. It will only create a new instance
     * if no Fima_Driver instance with the same parameters currently
     * exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Fima_Driver::singleton()
     *
     * @param string    $ledger     The name of the ledger to load.
     *
     * @param string    $driver     The type of concrete Fima_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The created concrete Fima_Driver instance, or false
     *                  on error.
     */
    function &singleton($ledger = '', $driver = null, $params = null)
    {
        static $instances;

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($ledger, $driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Fima_Driver::factory($ledger, $driver, $params);
        }

        return $instances[$signature];
    }

}
