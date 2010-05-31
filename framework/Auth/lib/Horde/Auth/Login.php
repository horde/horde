<?php
/**
 * The Horde_Auth_login:: class provides a system login implementation of
 * the Horde authentication system.
 *
 * This Auth driver is useful if you have a shadow password system
 * where the Horde_Auth_Passwd driver doesn't work.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Login extends Horde_Auth_Base
{
    /**
     * List of users that should be excluded from being listed/handled
     * in any way by this driver.
     *
     * @var array
     */
    protected $_exclude = array(
        'root', 'daemon', 'bin', 'sys', 'sync', 'games', 'man', 'lp', 'mail',
        'news', 'uucp', 'proxy', 'postgres', 'www-data', 'backup', 'operator',
        'list', 'irc', 'gnats', 'nobody', 'identd', 'sshd', 'gdm', 'postfix',
        'mysql', 'cyrus', 'ftp'
    );

    /**
     * Constructs a new Login authentication object.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'location' - (string) Location of the su binary.
     *              DEFAULT: /bin/su
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (empty($params['location'])) {
            $params['location'] = '/bin/su';
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        $proc = @popen($this->_location . ' -c /bin/true ' . $userId, 'w');
        if (!is_resource($proc)) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        fwrite($proc, $credentials['password']);
        if (@pclose($proc) !== 0) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }
    }

}
