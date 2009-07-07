<?php
/**
 * The Horde_Auth_Customsql class provides a sql implementation of the Horde
 * authentication system with the possibility to set custom-made queries.
 *
 * Required parameters: See Horde_Auth_Sql driver.
 * <pre>
 * Some special tokens can be used in the sql query. They are replaced
 * at the query stage:
 *
 *   - '\L' will be replaced by the user's login
 *   - '\P' will be replaced by the user's password.
 *   - '\O' will be replaced by the old user's login (required for update)
 *
 *   Eg: "SELECT * FROM users WHERE uid = \L
 *                            AND passwd = \P
 *                            AND billing = 'paid'
 *
 *   'query_auth'    Authenticate the user.       '\L' & '\P'
 *   'query_add'     Add user.                    '\L' & '\P'
 *   'query_getpw'   Get one user's password.     '\L'
 *   'query_update'  Update user.                 '\O', '\L' & '\P'
 *   'query_resetpassword'  Reset password.       '\L', & '\P'
 *   'query_remove'  Remove user.                 '\L'
 *   'query_list'    List user.
 *   'query_exists'  Check for existance of user. '\L'
 * </pre>
 *
 * Optional parameters: See Horde_Auth_Sql driver.
 *
 * Copyright 2002 Ronnie Garcia <ronnie@mk2.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Ronnie Garcia <ronnie@mk2.net>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Horde_Auth
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
        'update' => true
    );

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        Horde::assertDriverConfig($params, 'auth',
            array('query_auth'),
            'authentication custom SQL');

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            throw new Horde_Exception('', Horde_Auth::REASON_FAILED);
        }

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_auth'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);
        $query = str_replace('\P', $this->_db->quote(Horde_Auth::getCryptedPassword(
                                                         $credentials['password'],
                                                         $this->_getPassword($userId),
                                                         $this->_params['encryption'],
                                                         $this->_params['show_encryption'])), $query);

        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception('', Horde_Auth::REASON_FAILED);
        }

        $row = $result->fetchRow(DB_GETMODE_ASSOC);

        /* If we have at least one returned row, then the user is valid. */
        if (is_array($row)) {
            $result->free();
            return;
        }

        $result->free();
        throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to add.
     *
     * @throws Horde_Exception
     */
    public function addUser($userId, $credentials)
    {
        $this->_connect();

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_add'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);
        $query = str_replace('\P', $this->_db->quote(Horde_Auth::getCryptedPassword(
                                                         $credentials['password'], '',
                                                         $this->_params['encryption'],
                                                         $this->_params['show_encryption'])), $query);

        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldId       The old userId.
     * @param string $newId       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Exception
     */
    function updateUser($oldId, $newId, $credentials)
    {
        $this->_connect();

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_update'];
        $query = str_replace('\O', $this->_db->quote($oldId), $query);
        $query = str_replace('\L', $this->_db->quote($newId), $query);
        $query = str_replace('\P', $this->_db->quote(Horde_Auth::getCryptedPassword(
                                                         $credentials['password'],
                                                         $this->_getPassword($oldId),
                                                         $this->_params['encryption'],
                                                         $this->_params['show_encryption'])), $query);

        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }
    }

    /**
     * Resets a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Exception
     */
    public function resetPassword($userId)
    {
        $this->_connect();

        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Build the SQL query. */
        $query = $this->_params['query_resetpassword'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);
        $query = str_replace('\P', $this->_db->quote(Horde_Auth::getCryptedPassword($password,
                                                                               '',
                                                                               $this->_params['encryption'],
                                                                               $this->_params['show_encryption'])), $query);

        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }

        return $password;
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Exception
     */
    public function removeUser($userId)
    {
        $this->_connect();

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_remove'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);

        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }

        $this->removeUserData($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Exception
     */
    public function listUsers()
    {
        $this->_connect();

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_list'];
        $query = str_replace('\L', $this->_db->quote(Horde_Auth::getAuth()), $query);

        $result = $this->_db->getAll($query, null, DB_FETCHMODE_ORDERED);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
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
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            return false;
        }

        /* Build a custom query, based on the config file. */
        $query = $this->_params['query_exists'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);

        $result = $this->_db->getOne($query);

        return ($result instanceof PEAR_Error)
            ? false
            : (bool)$result;
    }

    /**
     * Fetch $userId's current password - needed for the salt with some
     * encryption schemes when doing authentication or updates.
     *
     * @param string $userId  TODO
     *
     * @return string  $userId's current password.
     */
    protected function _getPassword($userId)
    {
        /* Retrieve the old password in case we need the salt. */
        $query = $this->_params['query_getpw'];
        $query = str_replace('\L', $this->_db->quote($userId), $query);
        $pw = $this->_db->getOne($query);
        if ($pw instanceof PEAR_Error) {
            Horde::logMessage($pw, __FILE__, __LINE__, PEAR_LOG_ERR);
            return '';
        }

        return $pw;
    }

}
