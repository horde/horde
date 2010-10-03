<?php
/**
 * Fima storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'table'         The name of the foo table in 'database'.
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'database'      The name of the database.
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/sql/fima.sql
 * script.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Driver_sql extends Fima_Driver {

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $ledger  The ledger to load.
     * @param array $params   A hash containing connection parameters.
     */
    function Fima_Driver_sql($ledger, $params = array())
    {
        $this->_ledger = $ledger;
        $this->_params = $params;
    }

    /**
     * Retrieves accounts from the database.
     *
     * @param array $filters  Any filters for restricting the retrieved accounts.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieveAccounts($filters = array())
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE account_owner = ?', $this->_params['table_accounts']);
        $values = array($this->_ledger);

        /* Add filters. */
        $this->_addFilters($filters, $query, $values, 'account_');

        /* Sorting. */
        $query .= ' ORDER BY account_number ASC';

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::retrieveAccounts(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $this->_accounts = array();
        $result = $this->_db->query($query, $values);

        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }

            /* Store the retrieved values in the accounts variable. */
            $this->_accounts = array();
            while ($row && !is_a($row, 'PEAR_Error')) {
                /* Add this new account to the $_account list. */
                $this->_accounts[$row['account_id']] = $this->_buildAccount($row);

                /* Advance to the new row in the result set. */
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            }
            $result->free();
        } else {
            return $result;
        }

        return true;
    }

    /**
     * Retrieves one account from the database.
     *
     * @param string $accountId  The ID of the account to retrieve.
     *
     * @return array  The array of account attributes.
     */
    function getAccount($accountId)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE account_owner = ? AND account_id = ?',
                         $this->_params['table_accounts']);
        $values = array($this->_ledger, $accountId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::getAccount(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }
        if ($row === null) {
            return PEAR::raiseError(_("Not found"));
        }

        /* Decode and return the account. */
        return $this->_buildAccount($row);
    }

    /**
     * Retrieves one account from the database by number.
     *
     * @param string $number  The number of the account to retrieve.
     *
     * @return array  The array of account attributes.
     */
    function getAccountByNumber($number)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE account_owner = ? AND account_number = ?',
                         $this->_params['table_accounts']);
        $values = array($this->_ledger, sprintf('%\'04s', $number));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::getAccountByNumber(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }
        if ($row === null) {
            return PEAR::raiseError(_("Not found"));
        }

        /* Decode and return the account. */
        return $this->_buildAccount($row);
    }

    /**
     * Retrieves postings from the database.
     *
     * @param array $filters  Any filters for restricting the retrieved postings.
     * @param array $sorting  Sort order of retrieved postings.
     * @param array $limit	  Limit of the retrieved postings, array(page, postings/page).
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrievePostings($filters = array(), $sorting = array(), $limit = array())
    {
        /* Build the SQL query filter. */
        $queryfilter = ' WHERE posting_owner = ?';
        $values = array($this->_ledger);

        /* Add filters. */
        $this->_addFilters($filters, $queryfilter, $values, 'posting_');

        $query = sprintf('SELECT count(p.posting_id) posting_count, SUM(p.posting_amount) posting_result FROM %s p',
                         $this->_params['table_postings']);
        $query .= $queryfilter;

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::retrievePostings(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }
            $this->_postingsCount = (int)$row['posting_count'];
            $this->_postingsResult = $row['posting_result'];
            $result->free();

            // correct result when account is an asset account too
            if ($this->_postingsCount > 0) {
                $query = sprintf('SELECT SUM(p.posting_amount) posting_result ' .
                                 'FROM %s p JOIN %s a ON a.account_id = p.posting_account ' .
                                 $queryfilter . ' AND a.account_type = ?',
                                 $this->_params['table_postings'], $this->_params['table_accounts']);
                $values2 = $values;
                $values2[] = FIMA_ACCOUNTTYPE_ASSET;
                $result = $this->_db->query($query, $values2);
                if (isset($result) && !is_a($result, 'PEAR_Error')) {
                    $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
                    if (is_a($row, 'PEAR_Error')) {
                        return $row;
                    }
                }
                $this->_postingsResult -= $row['posting_result'];
                $result->free();
            }
        } else {
            return $result;
        }

        /* Fetch the postings if necessary. */
        $this->_postings = array();
        if ($this->_postingsCount == 0) {
            return true;
        }

        $query = sprintf('SELECT p.*, asset.account_number posting_asset_number, account.account_number posting_account_number ' .
                         'FROM %s p LEFT OUTER JOIN %s asset ON p.posting_asset = asset.account_id LEFT OUTER JOIN %s account ON p.posting_account = account.account_id',
                         $this->_params['table_postings'], $this->_params['table_accounts'], $this->_params['table_accounts']);
        $query .= $queryfilter;

        /* Sorting. */
        if (!is_array($sorting)) {
            $sorting = array($sorting);
        }
        if (count($sorting) == 0) {
            $sorting = array('posting_date ASC');
        }
        $query .= ' ORDER BY ' . implode(', ', $sorting);

        /* Limit. */
        if (count($limit) > 0) {
            if ($limit[0] < 0) {
                $limit[0] += ceil($this->_postingsCount / $limit[1]) + 1;
            }
            $limit[0] = ($limit[0] - 1) * $limit[1];
            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Fima_Driver_sql::retrievePostings() limitQuery: %s', $query), 'DEBUG');
            $result = $this->_db->queryLimit($query, $limit[0], $limit[1], $values);
        } else {
            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Fima_Driver_sql::retrievePostings(): %s', $query), 'DEBUG');
            $result = $this->_db->query($query, $values);
        }

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }

            /* Store the retrieved values in the accounts variable. */
            while ($row && !is_a($row, 'PEAR_Error')) {
                /* Add this new posting to the $_posting list. */
                $this->_postings[$row['posting_id']] = $this->_buildPosting($row);

                /* Advance to the new row in the result set. */
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            }
            $result->free();
        } else {
            return $result;
        }

        return true;
    }

    /**
     * Retrieves one posting from the database.
     *
     * @param string $postingId  The ID of the posting to retrieve.
     *
     * @return array  The array of posting attributes.
     */
    function getPosting($postingId)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE posting_owner = ? AND posting_id = ?',
                         $this->_params['table_postings']);
        $values = array($this->_ledger, $postingId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::getPosting(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);

        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }
        if ($row === null) {
            return PEAR::raiseError(_("Not found"));
        }

        /* Decode and return the posting. */
        return $this->_buildPosting($row);
    }

    /**
     * Get grouped results.
     *
     * @param array $groups     Fields to group.
     * @param boolean $filters  Filters for postings.
     *
     * @return array            A matrix of the grouped results.
     */
    function getResults($groups, $filters = array()) {
        $matrix = array();

        /* Fix grouping. */
        if (!is_array($groups)) {
            $groups = array($groups);
        }
        if (!isset($groups[1])) {
            $groups[1] = 'owner';
        }
        foreach ($groups as $groupId => $group) {
            switch($group) {
            case 'date_month':     $groups[$groupId] = 'FROM_UNIXTIME(posting_date, \'%Y%m\')'; break;
            case 'date_year':      $groups[$groupId] = 'FROM_UNIXTIME(posting_date, \'%Y\')'; break;
            case 'asset_number':   $groups[$groupId] = 'asset.account_number'; break;
            case 'asset_parent':   $groups[$groupId] = 'CONCAT(LEFT(asset.account_number, 2), \'00\')'; break;
            case 'asset_type':     $groups[$groupId] = 'asset.account_type'; break;
            case 'account_number': $groups[$groupId] = 'account.account_number'; break;
            case 'account_parent': $groups[$groupId] = 'CONCAT(LEFT(account.account_number, 2), \'00\')'; break;
            case 'account_type':   $groups[$groupId] = 'account.account_type'; break;
            default:               $groups[$groupId] = 'posting_'.$group; break;
            }
        }

        /* Build the SQL query filter. */
        $query = sprintf('SELECT %s x, %s y, sum(posting_amount) result ' .
                         'FROM %s p LEFT OUTER JOIN %s asset ON p.posting_asset = asset.account_id AND p.posting_owner = asset.account_owner LEFT OUTER JOIN %s account ON p.posting_account = account.account_id AND p.posting_owner = account.account_owner ' .
                         'WHERE posting_owner = ?',
                         $groups[0], $groups[1], $this->_params['table_postings'], $this->_params['table_accounts'], $this->_params['table_accounts']);
        $values = array($this->_ledger);

        /* Add filters. */
        foreach ($filters as $filterId => $filter) {
            switch($filter[0]) {
            case 'date_month':     $filters[$filterId][0] = 'FROM_UNIXTIME(posting_date, \'%Y%m\')'; break;
            case 'date_year':      $filters[$filterId][0] = 'FROM_UNIXTIME(posting_date, \'%Y%m\')'; break;
            case 'asset_number':   $filters[$filterId][0] = 'asset.account_number'; break;
            case 'asset_parent':   $filters[$filterId][0] = 'LEFT(asset.account_number, 2)'; break;
            case 'asset_type':     $filters[$filterId][0] = 'asset.account_type'; break;
            case 'account_number': $filters[$filterId][0] = 'account.account_number'; break;
            case 'account_parent': $filters[$filterId][0] = 'LEFT(account.account_number, 2)'; break;
            case 'account_type':   $filters[$filterId][0] = 'account.account_type'; break;
            default:               $filters[$filterId][0] = 'posting_'.$filter[0]; break;
            }
        }
        $this->_addFilters($filters, $query, $values);

        /* Add grouping. */
        $query .= ' GROUP BY ' . implode(', ', $groups);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::getResults(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }

            /* Store the retrieved values in the accounts variable. */
            while ($row && !is_a($row, 'PEAR_Error')) {
                /* Add this new posting to the $_posting list. */
                if (!isset($matrix[$row['y']])) {
                    $matrix[$row['y']] = array();
                }
                $matrix[$row['y']][$row['x']] = $row['result'];

                /* Advance to the new row in the result set. */
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            }
            $result->free();
        }

        return $matrix;
    }

    /**
     * Get the results of all asset accounts.
     *
     * @param string $postingtype  Type of postings.
     * @param boolean $perdate     Date of asset results.
     *
     * @return array  Array of asset accounts and results
     */
    function getAssetResults($postingtype, $perdate = null)
    {
        $perdate = ($perdate === null) ? mktime() : (int)$perdate;

        /* Build the SQL query. */
        $query = sprintf('SELECT account_id, SUM(account_result) account_result FROM ( ' .
                         ' SELECT a1.account_id, SUM(p1.posting_amount) account_result ' .
                         ' FROM %s a1 LEFT OUTER JOIN %s p1 ON a1.account_id = p1.posting_asset AND p1.posting_owner = ? AND p1.posting_type = ? ' .
                         ' WHERE a1.account_owner = ? AND a1.account_type = ? and p1.posting_date <= ?' .
                         ' GROUP BY a1.account_id ' .
                         '  UNION ' .
                         ' SELECT a2.account_id, SUM(p2.posting_amount) * -1 account_result ' .
                         ' FROM %s a2 LEFT OUTER JOIN %s p2 ON a2.account_id = p2.posting_account AND p2.posting_owner = ? AND p2.posting_type = ? ' .
                         ' WHERE a2.account_owner = ? AND a2.account_type = ? and p2.posting_date <= ?' .
                         ' GROUP BY a2.account_id ' .
                         ') x ' .
                         'GROUP BY account_id ',
                         $this->_params['table_accounts'], $this->_params['table_postings'],
                         $this->_params['table_accounts'], $this->_params['table_postings']);
        $values = array($this->_ledger, $postingtype, $this->_ledger, FIMA_ACCOUNTTYPE_ASSET, $perdate,
                        $this->_ledger, $postingtype, $this->_ledger, FIMA_ACCOUNTTYPE_ASSET, $perdate);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::getAssetResults(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $assetresults = array();
        $result = $this->_db->query($query, $values);
        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }

            /* Store the retrieved values in the accounts variable. */
            while ($row && !is_a($row, 'PEAR_Error')) {
                /* Add this new posting to the $_posting list. */
                $assetresults[] = $row;

                /* Advance to the new row in the result set. */
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            }
            $result->free();
        } else {
            return $result;
        }

        return $assetresults;
    }

    /**
     * Build an account.
     *
     * @param array $row          Datasbase row holding account attributes.
     * @param boolean $getparent  Also get parent account.
     *
     * @return array  The array of account attributes.
     */
    function _buildAccount($row, $getparent = true)
    {
        $parent = null;
        if ($getparent) {
            if (($parent_number = Fima::getAccountParent($row['account_number'])) !== null) {
                if (isset($this->_accounts[$parent_number])) {
                    $parent = $this->_accounts[$parent_number];
                } else {
                    $parent = $this->getAccountByNumber($parent_number);
                    if (is_a($parent, 'PEAR_Error')) {
                        $parent = null;
                    }
                }
            }
        }

        /* Create a new account based on $row's values. */
        return array('account_id' => $row['account_id'],
                     'owner' => $row['account_owner'],
                     'number' => sprintf('%\'04d', $row['account_number']),
                     'type' => $row['account_type'],
                     'name' => Horde_String::convertCharset($row['account_name'], $this->_params['charset'], 'UTF-8'),
                     'eo' => $row['account_eo'],
                     'desc' => Horde_String::convertCharset($row['account_desc'], $this->_params['charset'], 'UTF-8'),
                     'closed' => $row['account_closed'],
                     'label' => trim($row['account_number'] . ' ' .
                                (($parent === null) ? '' : $parent['name'] . ' - ') .
                                Horde_String::convertCharset($row['account_name'], $this->_params['charset'], 'UTF-8')),
                     'parent_id' => ($parent === null) ? null : $parent['account_id'],
                     'parent_number' => ($parent === null) ? '' : $parent['number'],
                     'parent_name' => ($parent === null) ? '' : $parent['name']);
    }

    /**
     * Adds an account to the backend storage.
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
    function _addAccount($number, $type, $name, $eo, $desc, $closed)
    {
        $accountId = strval(new Horde_Support_Uuid());

        $query = sprintf(
            'INSERT INTO %s (account_id, account_owner, account_number, account_type, ' .
            'account_name, account_eo, account_desc, account_closed) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            $this->_params['table_accounts']);
        $values = array($accountId,
                        $this->_ledger,
                        sprintf('%\'04d', $number),
                        $type,
                        Horde_String::convertCharset($name, 'UTF-8', $this->_params['charset']),
                        (int)(bool)$eo,
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        (int)(bool)$closed);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_addAccount(): %s', $query), 'DEBUG');

        /* Attempt the insertion query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $accountId;
    }

    /**
     * Modifies an existing account.
     *
     * @param string $accountId  The account to modify.
     * @param string $number     The number of the account.
     * @param string $type       The type of the account.
     * @param string $name       The name (short) of the account.
     * @param boolean $eo	     Extraordinary account.
     * @param string $desc       The description (long) of the account.
     * @param boolean $closed    Close account.
     *
     * @return mixed             True or PEAR_Error
     */
    function _modifyAccount($accountId, $number, $type, $name, $eo, $desc, $closed)
    {
        $query = sprintf('UPDATE %s SET' .
                         ' account_number = ?, ' .
                         ' account_type = ?, ' .
                         ' account_name = ?, ' .
                         ' account_eo = ?, ' .
                         ' account_desc = ?, ' .
                         ' account_closed = ? ' .
                         'WHERE account_owner = ? AND account_id = ?',
                         $this->_params['table_accounts']);
        $values = array(sprintf('%\'04d', $number),
                        $type,
                        Horde_String::convertCharset($name, 'UTF-8', $this->_params['charset']),
                        (int)(bool)$eo,
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        (int)(bool)$closed,
                        $this->_ledger,
                        $accountId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_modifyAccount(): %s', $query), 'DEBUG');

        /* Attempt the update query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Deletes an account from the backend.
     *
     * @param string $accountId     The account to delete.
     * @param mixed $dsSubaccounts  True/false when deleting subaccounts,
     * 								accountId when shifting subaccounts
     * @param mixed $dsPostings     True/false when deleting postings,
     * 								accountId when shifting postings
     *
     * @return mixed                True or PEAR_Error
     */
    function _deleteAccount($accountId, $dsSubaccounts = false, $dsPostings = true)
    {
        /* Get the account's details for use later. */
        $account = $this->getAccount($accountId);

        /* Handle subaccounts. */
        if ($dsSubaccounts !== false) {
            /* Delete subaccounts. */
            $parent = (int)($account['number'] / 100) . '%';
            $this->retrieveAccounts(array(array('number', $parent, 'LIKE'),
                                          array('number', (string)$account['number'], '!=')));

            foreach ($this->_accounts as $subaccountId => $subaccount) {
                $delete = $this->_deleteAccount($subaccountId, false, $dsSubaccounts);
                if (is_a($delete, 'PEAR_Error')) {
                    return $delete;
                }
            }
        }

        /* Handle postings. */
        if ($dsPostings !== false) {
            if ($dsPostings === true) {
                /* Delete account postings. */
                $query = sprintf('DELETE FROM %s WHERE posting_owner = ? AND (posting_asset = ? OR posting_account = ?)',
                                 $this->_params['table_postings']);
                $values = array($this->_ledger, $accountId, $accountId);

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Fima_Driver_sql::_deleteAccount(): %s', $query), 'DEBUG');

                /* Attempt the delete query. */
                $result = $this->_db->query($query, $values);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, 'ERR');
                    return $result;
                }
            } else {
                /* Shift account postings. */
                $shift = $this->_shiftPostings($accountId, $dsPostings);
                if (is_a($shift, 'PEAR_Error')) {
                    return $shift;
                }
            }
        }

        /* Delete account. */
        $query = sprintf('DELETE FROM %s WHERE account_owner = ? AND account_id = ?',
                         $this->_params['table_accounts']);
        $values = array($this->_ledger, $accountId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_deleteAccount(): %s', $query), 'DEBUG');

        /* Attempt the delete query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Build a posting.
     *
     * @param array $row  Datasbase row holding posting attributes;
     *
     * @return array  The array of posting attributes.
     */
    function _buildPosting($row)
    {
        /* Create a new account based on $row's values. */
        return array('posting_id' => $row['posting_id'],
                     'owner' => $row['posting_owner'],
                     'type' => $row['posting_type'],
                     'date' => $row['posting_date'],
                     'asset' => $row['posting_asset'],
                     'account' => $row['posting_account'],
                     'desc' => Horde_String::convertCharset($row['posting_desc'], $this->_params['charset'], 'UTF-8'),
                     'amount' => $row['posting_amount'],
                     'eo' => (int)(bool)$row['posting_eo']);
    }

    /**
     * Adds a posting to the backend storage.
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
    function _addPosting($type, $date, $asset, $account, $eo, $amount, $desc)
    {
        $postingId = strval(new Horde_Support_Uuid());

        $query = sprintf(
            'INSERT INTO %s (posting_id, posting_owner, posting_type, posting_date, ' .
            'posting_asset, posting_account, posting_eo, posting_amount, posting_desc) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $this->_params['table_postings']);
        $values = array($postingId,
                        $this->_ledger,
                        $type,
                        (int)$date,
                        $asset,
                        $account,
                        (int)(bool)$eo,
                        (float)$amount,
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_addPosting(): %s', $query), 'DEBUG');

        /* Attempt the insertion query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

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
    function _modifyPosting($postingId, $type, $date, $asset, $account, $eo, $amount, $desc)
    {
        $query = sprintf('UPDATE %s SET' .
                         ' posting_type = ?, ' .
                         ' posting_date = ?, ' .
                         ' posting_asset = ?, ' .
                         ' posting_account = ?, ' .
                         ' posting_eo = ?, ' .
                         ' posting_amount = ?, ' .
                         ' posting_desc = ? ' .
                         'WHERE posting_owner = ? AND posting_id = ?',
                         $this->_params['table_postings']);
        $values = array($type,
                        (int)$date,
                        $asset,
                        $account,
                        (int)(bool)$eo,
                        (float)$amount,
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        $this->_ledger,
                        $postingId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_modifyPosting(): %s', $query), 'DEBUG');

        /* Attempt the update query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Deletes a posting from the backend.
     *
     * @param string $postingId     The posting to delete.
     *
     * @return mixed                True or PEAR_Error
     */
    function _deletePosting($postingId)
    {
        /* Get the task's details for use later. */
        $posting = $this->getPosting($postingId);

        $query = sprintf('DELETE FROM %s WHERE posting_owner = ? AND posting_id = ?',
                         $this->_params['table_postings']);
        $values = array($this->_ledger, $postingId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_deletePosting(): %s', $query), 'DEBUG');

        /* Attempt the delete query. */
        $result = $this->_db->query($query, $values);

        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Shift an existing posting.
     *
     * @param string $postingId  The posting to shift.
     * @param string $type		 The posting type shifting to.
     * @param string $asset      The ID of the asset account.
     * @param string $account    The ID of the account.
     *
     * @return mixed             True or PEAR_Error
     */
    function _shiftPosting($postingId, $type, $asset, $account)
    {
        if (!$type && !$asset && !$account) {
            return true;
        }

        $query = sprintf('UPDATE %s SET' .
                         ($type ? ' posting_type = ?, ' : '') .
                         ($asset ? ' posting_asset = ?, ' : '') .
                         ($account ? ' posting_account = ?, ' : '').
                         ' posting_eo = posting_eo ' .
                         'WHERE posting_owner = ? AND posting_id = ?',
                         $this->_params['table_postings']);
        $values = array();
        if ($type)    { $values[] = $type; }
        if ($asset)   { $values[] = $asset; }
        if ($account) { $values[] = $account; }
        $values[] = $this->_ledger;
        $values[] = $postingId;

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Fima_Driver_sql::_shiftPosting(): %s', $query), 'DEBUG');

        /* Attempt the update query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    /**
     * Shift postings in the backend.
     *
     * @param mixed $accountIdFrom  The account(s) to shift postings from.
     * @param string $accountIdTo   The account to shift postings to.
     *
     * @return mixed                True or PEAR_Error
     */
    function _shiftPostings($accountIdFrom, $accountIdTo)
    {
        if (!is_array($accountIdFrom)) {
            $accountIdFrom = array($accountIdFrom);
        }

        foreach ($accountIdFrom as $key => $value) {
            $accountIdFrom[$key] = $this->_db->quoteSmart($value);
        }

        $fields = array('posting_asset', 'posting_account');
        foreach ($fields as $field) {
            $query = sprintf('UPDATE %s SET' .
                             ' %s = ? ' .
                             'WHERE posting_owner = ? AND %s IN (!)',
                             $this->_params['table_postings'], $field, $field);
            $values = array($accountIdTo, $this->_ledger, implode(',', $accountIdFrom));

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Fima_Driver_sql::_shiftPostings(): %s', $query), 'DEBUG');

            /* Attempt the update query. */
            $result = $this->_db->query($query, $values);

            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return true;
    }

    /**
     * Deletes all postings and accounts.
     *
     * @param mixed $accounts  boolean or account_type
     * @param mixed $accounts  boolean or posting_type.
     *
     * @return mixed                True or PEAR_Error
     */
    function _deleteAll($accounts, $postings)
    {
        /* Delete postings. */
        if ($postings) {
            $query = sprintf('DELETE FROM %s WHERE posting_owner = ?',
                             $this->_params['table_postings']);
            $values = array($this->_ledger);

            /* Filter. */
            if ($postings !== true) {
                $query .= ' AND posting_type = ?';
                $values[] = $postings;
            }

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Fima_Driver_sql::_deleteAll(): %s', $query), 'DEBUG');

            /* Attempt the delete query. */
            $result = $this->_db->query($query, $values);

            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        } else {
            /* If postings aren't deleted, don't delete accounts. */
            return false;
        }

        /* Delete Accounts */
        if ($accounts) {
            $query = sprintf('DELETE FROM %s WHERE account_owner = ?',
                             $this->_params['table_accounts']);
            $values = array($this->_ledger);

            /* Filter. */
            if ($accounts !== true) {
                $query .= ' AND account_type = ?';
                $values[] = $accounts;
            }

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Fima_Driver_sql::_deleteAll(): %s', $query), 'DEBUG');

            /* Attempt the delete query. */
            $result = $this->_db->query($query, $values);

            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return true;
    }


    /**
     * Build the where clause for a query using the passed filters
     * Attention: does not include the WHERE keyword, add WHERE 1=1 manually in the query
     *
     * @param array $filters  Array of filters, syntax: array(field, value [, operator = '=' [, andor = 'AND']])
     * @param array $prefix   optional prefix for fields
     *
     * @return integer   number of added filters
     */
    function _addFilters($filters, &$query, &$values, $prefix = '')
    {
        $filtercnt = 0;

        foreach ($filters as $filter) {
            // and/or
            if (!isset($filter[3])) {
                $filter[3] = 'AND';
            } else {
                $filter[3] = strtoupper($filter[3]);
                if (!in_array($filter[3], array('AND', 'OR'))) {
                    $filter[3] = 'AND';
                }
            }

            // subfilter
            if (is_array($filter[0])) {
                $query .= ' ' . $filter[3] . ' (1=1';
                $filtercnt += $this->_addFilters($filter[0], $query, $values, $prefix);
                $query .= ')';
                continue;
            }

            // fix operator
            if (!isset($filter[2])) {
                $filter[2] = '=';
            } else {
                $filter[2] = strtoupper($filter[2]);
                if (!in_array($filter[2], array('<', '>', '<=', '>=', '=', '<>', '!=', 'IN', 'NOT IN', 'IS', 'IS NOT', 'LIKE', 'NOT LIKE'))) {
                    $filter[2] = '=';
                }
            }

            // fix operator for null values
            if ($filter[1] === null) {
                if (!in_array($filter[2], array('IS', 'IS NOT'))) {
                    $filter[2] = in_array($filter[2], array('=', 'IN', 'LIKE')) ? 'IS' : 'IS NOT';
                }
            } elseif (in_array($filter[2], array('IS', 'IS NOT'))) {
                $filter[2] = ($filter[2] == 'IS') ? '=' : '!=';
            }

            // fix operator for array value + prepare values
            if (is_array($filter[1])) {
                if (!in_array($filter[2], array('IN', 'NOT IN'))) {
                   $filter[2] = in_array($filter[2], array('=', 'IS', 'LIKE')) ? 'IN' : 'NOT IN';
                }
                $filterph = '(!)';
                foreach ($filter[1] as $key => $value) {
                    $filter[1][$key] = $this->_db->quoteSmart($value);
                }
                $filter[1] = implode(',', $filter[1]);
            } else {
                if (in_array($filter[2], array('IN', 'NOT IN'))) {
                    $filter[2] = ($filter[2] == 'IN') ? '=' : '!=';
                }
                $filterph = '?';
            }

            // fix != operator
            if ($filter[2] == '!=') {
                $filter[2] = '<>';
            }

            $query .= sprintf(' ' . $filter[3] . ' ' . $prefix . '%s %s %s', $filter[0], $filter[2], $filterph);
            $values[] = $filter[1];
            $filtercnt++;
        }

        return $filtercnt;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean  True on success; PEAR_Error on failure.
     */
    function initialize()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'fima', 'storage');
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return true;
    }

}
