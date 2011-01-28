<?php
/**
 * Implementation of the Quota API for servers keeping quota information in a
 * custom SQL database.
 *
 * Copyright 2006-2007 Tomas Simonaitis <haden@homelan.lt>
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Tomas Simonaitis <haden@homelan.lt>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota_Sql extends IMP_Quota_Base
{
    /**
     * DB object.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'query_quota' - (string) SQL query which returns single row/column with
     *                 user quota (in bytes). %u is replaced with current user
     *                 name, %U with the user name without the domain part, %d
     *                 with the domain.
     * 'query_used' - (string) SQL query which returns single row/column with
     *                user used space (in bytes). Placeholders are the same
     *                as in 'query_quota'.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['db'])) {
            throw new IMP_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'query_quota' => null,
            'query_used' => null
        ), $params);

        parent::__construct($params);
    }

    /**
     * Returns quota information.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'limit' - Maximum quota allowed
     * 'usage' - Currently used portion of quota (in bytes)
     * </pre>
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        $quota = array(
            'limit' => 0,
            'usage' => 0
        );

        if (empty($this->_params['query_quota'])) {
            Horde::logMessage(__CLASS__ . ': query_quota SQL query not set.', 'ERR');
        } else {
            @list($bare_user, $domain) = explode('@', $this->_params['username'], 2);
            $query = str_replace(array('?', '%u', '%U', '%d'),
                                 array($this->_db->quote($this->_params['username']),
                                       $this->_db->quote($this->_params['username']),
                                       $this->_db->quote($bare_user),
                                       $this->_db->quote($domain)),
                                 $this->_params['query_quota']);
            try {
                $result = $this->_db->selectOne($query);
            } catch (Horde_Db_Exception $e) {
                throw new IMP_Exception($e);
            }

            $quota['limit'] = $result;
        }

        if (empty($this->_params['query_used'])) {
            Horde::logMessage(__CLASS__ . ': query_used SQL query not set.', 'ERR');
        } else {
            @list($bare_user, $domain) = explode('@', $this->_params['username'], 2);
            $query = str_replace(array('?', '%u', '%U', '%d'),
                                 array($this->_db->quote($this->_params['username']),
                                       $this->_db->quote($this->_params['username']),
                                       $this->_db->quote($bare_user),
                                       $this->_db->quote($domain)),
                                 $this->_params['query_used']);
            try {
                $result = $this->_db->selectOne($query);
            } catch (Horde_Db_Exception $e) {
                throw new IMP_Exception($e);
            }

            $quota['usage'] = $result;
        }

        return $quota;
    }

}
