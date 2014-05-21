<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */

/**
 * Autoconfigured configuration details for a POP3 server (RFC 1939/STD 53).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Server_Pop3 extends Horde_Mail_Autoconfig_Server
{
    /**
     * Default POP3 port (RFC 1939 [3]).
     */
    public $port = 110;

    /**
     */
    public function valid(array $opts = array())
    {
        if (empty($opts['users'])) {
            unset($opts['auth']);
            /* We still need a username as it is required by the POP3
             * object. */
            $opts['users'] = array('testing');
        }

        switch ($this->tls) {
        case 'starttls':
            $secure = 'tls';
            break;

        case 'tls':
            $secure = 'ssl';
            break;

        default:
            $secure = !empty($opts['insecure']) ?: 'tls';
            break;
        }

        foreach ($opts['users'] as $user) {
            try {
                $pop3 = new Horde_Imap_Client_Socket_Pop3(array(
                    'hostspec' => $this->host,
                    'password' => isset($opts['auth']) ? $opts['auth'] : null,
                    'port' => $this->port,
                    'secure' => $secure,
                    'timeout' => 2,
                    'username' => $user
                ));

                if (isset($opts['auth'])) {
                    $pop3->login();
                    $this->username = $user;
                } else {
                    $pop3->noop();
                }

                if (isset($opts['auth'])) {
                    $this->username = $user;
                }
                if ($secure === 'tls') {
                    $this->tls = 'starttls';
                } elseif ($secure === true) {
                    $this->tls = $pop3->isSecureConnection()
                        ? 'starttls'
                        : false;
                }

                $pop3->shutdown();

                return true;
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return false;
    }

}
