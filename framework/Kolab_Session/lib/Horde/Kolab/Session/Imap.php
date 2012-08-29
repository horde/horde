<?php
/**
 * The Horde_Kolab_Session_Imap class relies on predefined Kolab user
 * details and validates the credentials against the IMAP server only.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * The Horde_Kolab_Session_Imap class relies on predefined Kolab user
 * details and validates the credentials against the IMAP server only.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Imap extends Horde_Kolab_Session_Abstract
{
    /**
     * Kolab configuration parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * The imap driver factory..
     *
     * @var Horde_Kolab_Session_Factory_Imap
     */
    private $_imap;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session_Factory_Imap $imap    The imap driver factory.
     * @param array                            $params  Kolab configuration
     *                                                  settings.
     */
    public function __construct(
        Horde_Kolab_Session_Factory_Imap $imap,
        array $params
    )
    {
        $this->_imap   = $imap;
        $this->_params = $params;
    }

    /**
     * Try to connect the session handler.
     *
     * @param string $user_id     The user ID to connect with.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect($user_id = null, array $credentials = null)
    {
        $this->_data['user']['id'] = $user_id;
        if (isset($credentials['password'])) {
            $password = $credentials['password'];
        } else {
            $password = '';
        }

        $user_list = array();
        foreach (array_keys($this->_params['users']) as $user) {
            $user_list[$user] = $user;
        }
        foreach ($this->_params['users'] as $user => $details) {
            if (isset($details['user']['uid'])) {
                $user_list[$details['user']['uid']] = $user;
            }
        }

        if (!in_array($user_id, array_keys($user_list))) {
            throw new Horde_Kolab_Session_Exception_Badlogin('Invalid credentials!', 0);
        }

        $details = $this->_params['users'][$user_list[$user_id]];
        if (!isset($details['imap']['server'])) {
            if (isset($this->_params['imap']['server'])) {
                $details['imap']['server'] = $this->_params['imap']['server'];
            } else {
                $details['imap']['server'] = 'localhost';
            }
        }

        if (isset($this->_params['imap']['port'])) {
            $port = $this->_params['imap']['port'];
        } else {
            $port = 143;
        }

        $imap = $this->_imap->create(
            array(
                'hostspec' => $details['imap']['server'],
                'username' => $user_id,
                'password' => $password,
                'port'     => $port,
                'secure'   => 'tls'
            )
        );

        try {
            $imap->login();
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() >= 100 && $e->getCode() < 200) {
                throw new Horde_Kolab_Session_Exception_Badlogin('Invalid credentials!', 0, $e);
            } else {
                throw new Horde_Kolab_Session_Exception('Login failed!', 0, $e);
            }
        }

        $details['user']['id'] = $user_id;

        if (!isset($details['fb']['server'])) {
            if (isset($this->_params['freebusy']['url'])) {
                $details['fb']['server'] = $this->_params['freebusy']['url'];
            } else {
                $fb_server = $details['imap']['server'];
                if (isset($this->_params['freebusy']['url_format'])) {
                    $fb_format = $this->_params['freebusy']['url_format'];
                } else {
                    $fb_format = 'http://%s/freebusy';
                }
                $details['fb']['server'] = sprintf($fb_format, $fb_server);
            }
        }

        $this->_data = $details;
    }
}
