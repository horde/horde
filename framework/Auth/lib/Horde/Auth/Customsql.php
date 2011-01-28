<?php
/**
 * The Horde_Auth_Customsql class provides a sql implementation of the Horde
 * authentication system with the possibility to set custom-made queries.
 *
 * Copyright 2002 Ronnie Garcia <ronnie@mk2.net>
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Ronnie Garcia <ronnie@mk2.net>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Joel Vandal <joel@scopserv.com>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php
 * @package  Auth
 */
class Horde_Auth_Customsql extends Horde_Auth_Sql
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add' => true,
        'list' => true,
        'remove' => true,
        'resetpassword' => true,
        'update' => true,
        'authenticate' => true,
    );

    /**
     * Constructor.
     *
     * Some special tokens can be used in the SQL query. They are replaced
     * at the query stage:
     *   '\L' will be replaced by the user's login
     *   '\P' will be replaced by the user's password.
     *   '\O' will be replaced by the old user's login (required for update)
     *
     * Eg: "SELECT * FROM users WHERE uid = \L
     *                          AND passwd = \P
     *                          AND billing = 'paid'
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'query_auth' - (string) Authenticate the user. ('\L' & '\P')
     * 'query_add' - (string) Add user. ('\L' & '\P')
     * 'query_getpw' - (string) Get one user's password. ('\L')
     * 'query_update' - (string) Update user. ('\O', '\L' & '\P')
     * 'query_resetpassword' - (string) Reset password. ('\L', & '\P')
     * 'query_remove' - (string) Remove user. ('\L')
     * 'query_list' - (string) List user.
     * 'query_exists' - (string) Check for existance of user. ('\L')
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('query_auth', 'query_add', 'query_getpw',
                       'query_update', 'query_resetpassword', 'query_remove',
                       'query_list', 'query_exists') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            array('\L', '\P'),
            array(
                $this->_db->quote($userId),
                $this->_db->quote(Horde_Auth::getCryptedPassword($credentials['password'], $this->_getPassword($userId), $this->_params['encryption'], $this->_params['show_encryption']))
            ),
            $this->_params['query_auth']
        );

        try {
            if ($this->_db->selectValue($query)) {
                return;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to add.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            array('\L', 'P'),
            array(
                $this->_db->quote($userId),
                $this->_db->quote(Horde_Auth::getCryptedPassword($credentials['password'], '', $this->_params['encryption'], $this->_params['show_encryption']))
            ),
            $this->_params['query_add']
        );

        try {
            $this->_db->insert($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldId       The old userId.
     * @param string $newId       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldId, $newId, $credentials)
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            array('\O', '\L', '\P'),
            array(
                $this->_db->quote($oldId),
                $this->_db->quote($newId),
                $this->_db->quote(Horde_Auth::getCryptedPassword($credentials['password'], $this->_getPassword($oldId), $this->_params['encryption'], $this->_params['show_encryption']))
            ),
            $this->_params['query_update']
        );

        try {
            $this->_db->update($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Resets a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Build the SQL query. */
        $query = str_replace(
            array('\L', '\P'),
            array(
                $this->_db->quote($userId),
                $this->_db->quote(Horde_Auth::getCryptedPassword($password, '', $this->_params['encryption'], $this->_params['show_encryption']))
            ),
            $this->_params['query_resetpassword']
        );

        try {
            $this->_db->update($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        return $password;
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            '\L',
            $this->_db->quote($userId),
            $this->_params['query_remove']
        );

        try {
            $this->_db->delete($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        Horde_Auth::removeUserData($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            '\L',
            $this->_db->quote($this->_params['default_user']),
            $this->_params['query_list']
        );

        try {
            $result = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        /* Loop through and build return array. */
        $users = array();
        foreach ($result as $ar) {
            $users[] = $ar[0];
        }

        return $users;
    }

    /**
     * Checks if a userId exists in the system.
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        /* Build a custom query, based on the config file. */
        $query = str_replace(
            '\L',
            $this->_db->quote($userId),
            $this->_params['query_exists']
        );

        try {
            return (bool)$this->_db->selectValue($query);
        } catch (Horde_Db_Exception $e) {
            return false;
        }
    }

    /**
     * Fetch $userId's current password - needed for the salt with some
     * encryption schemes when doing authentication or updates.
     *
     * @param string $userId  The userId to query.
     *
     * @return string  $userId's current password.
     */
    protected function _getPassword($userId)
    {
        /* Retrieve the old password in case we need the salt. */
        $query = str_replace(
            '\L',
            $this->_db->quote($userId),
            $this->_params['query_getpw']
        );

        try {
            return $this->_db->selectValue($query);
        } catch (Horde_Db_Exception $e) {
            return null;
        }
    }

}
