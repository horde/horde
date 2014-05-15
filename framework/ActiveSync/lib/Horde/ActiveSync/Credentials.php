<?php
/**
 * Horde_ActiveSync_Credentials
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Provides an abstraction for obtaining the correct EAS credentials.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal  Not intended for use outside of the ActiveSync library.
 *
 * @property string username  The authentication username.
 * @property-read string password  The password, if available.
 */
class Horde_ActiveSync_Credentials
{
    /**
     * The server object.
     *
     * @var Horde_ActiveSync
     */
    protected $_server;

    /**
     * The user's credentials. Username is in index 0 and password is in
     * index 1.
     *
     * @var array
     */
    protected $_credentials = array();

    /**
     * Const'r
     *
     * @param Horde_ActiveSync $server The server object.
     */
    public function __construct(Horde_ActiveSync $server)
    {
        $this->_server = $server;
        $this->_credentials = $this->_getCredentials();
    }

    public function __get($property)
    {
        switch ($property) {
        case 'username':
            return $this->_credentials[0];
        case 'password':
            return $this->_credentials[1];
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {
        case 'username':
            $this->_credentials[0] = $value;
            break;
        default:
            throw new InvalidArgumentException(sprintf('%s is not a valid property.', $property));
        }
    }

    /**
     * Return the username and password to use for authentication.
     *
     * @return array  The username in index 0 and password in index 1.
     */
    protected function _getCredentials()
    {
        $user = $pass = '';
        $serverVars = $this->_server->request->getServerVars();
        if (!empty($serverVars['PHP_AUTH_PW'])) {
            $user = $serverVars['PHP_AUTH_USER'];
            $pass = $serverVars['PHP_AUTH_PW'];
        } elseif (!empty($serverVars['HTTP_AUTHORIZATION']) || !empty($serverVars['Authorization'])) {
            // Some clients use the non-standard 'Authorization' header.
            $authorization = !empty($serverVars['HTTP_AUTHORIZATION'])
                ? $serverVars['HTTP_AUTHORIZATION']
                : $serverVars['Authorization'];
            $hash = base64_decode(str_replace('Basic ', '', $authorization));
            if (strpos($hash, ':') !== false) {
                list($user, $pass) = explode(':', $hash, 2);
            }
        } else {
            // Might be using X509 certs, so won't have the Auth headers or a
            // password.
            $get = $this->_server->getGetVars();
            if (!empty($get['User'])) {
                $user = $get['User'];
            }
        }

        return array($user, $pass);
    }

}