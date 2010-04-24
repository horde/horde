<?php
/**
 * Implementation of the Quota API for servers keeping quota information in a
 * custom SQL database.
 *
 * You must configure this driver in imp/config/servers.php.  The driver
 * supports the following parameters:
 * <pre>
 * query_quota - (string) SQL query which returns single row/column with user
 *               quota (in bytes). %u is replaced with current user name, %U
 *               with the user name without the domain part, %d with the
 *               domain.
 * query_used - (string) SQL query which returns single row/column with user
 *              used space (in bytes). Placeholders are the same like in
 *              query_quota.
 * </pre>
 *
 * Additionally, the driver takes SQL connection parameters 'phptype',
 * 'hostspec',' 'username', 'password', and 'database'. See
 * horde/config/conf.php for further information on these parameters
 *
 * Copyright 2006-2007 Tomas Simonaitis <haden@homelan.lt>
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tomas Simonaitis <haden@homelan.lt>
 * @author  Jan Schneider <jan@horde.org>
 * @package IMP
 */
class IMP_Quota_Sql extends IMP_Quota
{
    /**
     * SQL connection object.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Connects to the database.
     *
     * @throws IMP_Exception
     */
    protected function _connect()
    {
        if ($this->_db) {
            return;
        }

        $this->_db = DB::connect($this->_params,
                                 array('persistent' => !empty($this->_params['persistent']),
                                       'ssl' => !empty($this->_params['ssl'])));
        if ($this->_db instanceof PEAR_Error) {
            throw new IMP_Exception(_("Unable to connect to SQL server."));
        }
    }

    /**
     * Returns quota information.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        $this->_connect();

        $user = $_SESSION['imp']['user'];
        $quota = array('limit' => 0, 'usage' => 0);

        if (!empty($this->_params['query_quota'])) {
            @list($bare_user, $domain) = explode('@', $user, 2);
            $query = str_replace(array('?', '%u', '%U', '%d'),
                                 array($this->_db->quote($user),
                                       $this->_db->quote($user),
                                       $this->_db->quote($bare_user),
                                       $this->_db->quote($domain)),
                                 $this->_params['query_quota']);
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                throw new IMP_Exception($result);
            }

            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_array($row)) {
                $quota['limit'] = current($row);
            }
        } else {
            Horde::logMessage('IMP_Quota_Sql: query_quota SQL query not set', 'DEBUG');
        }

        if (!empty($this->_params['query_used'])) {
            @list($bare_user, $domain) = explode('@', $user, 2);
            $query = str_replace(array('?', '%u', '%U', '%d'),
                                 array($this->_db->quote($user),
                                       $this->_db->quote($user),
                                       $this->_db->quote($bare_user),
                                       $this->_db->quote($domain)),
                                 $this->_params['query_used']);
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                throw new IMP_Exception($result);
            }

            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (is_array($row)) {
                $quota['usage'] = current($row);
            }
        } else {
            Horde::logMessage('IMP_Quota_Sql: query_used SQL query not set', 'DEBUG');
        }

        return $quota;
    }

}
