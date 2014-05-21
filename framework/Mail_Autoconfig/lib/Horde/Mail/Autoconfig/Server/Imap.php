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
 * Autoconfigured configuration details for an IMAP server (RFC 3501).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Server_Imap extends Horde_Mail_Autoconfig_Server
{
    /**
     * Default IMAP port (RFC 3501 [2.1]).
     */
    public $port = 143;

    /**
     */
    public function valid(array $opts = array())
    {
        if (empty($opts['users'])) {
            unset($opts['auth']);
            /* We still need a username as it is required by the IMAP
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
                $imap = new Horde_Imap_Client_Socket(array(
                    'hostspec' => $this->host,
                    'password' => isset($opts['auth']) ? $opts['auth'] : null,
                    'port' => $this->port,
                    'secure' => $secure,
                    'timeout' => 2,
                    'username' => $user
                ));

                if (isset($opts['auth'])) {
                    $imap->login();
                    $this->username = $user;
                } else {
                    $imap->noop();
                }

                if ($secure === 'tls') {
                    $this->tls = 'starttls';
                } elseif ($secure === true) {
                    $this->tls = $imap->isSecureConnection()
                        ? 'starttls'
                        : false;
                }

                $imap->shutdown();

                return true;
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return false;
    }

}
