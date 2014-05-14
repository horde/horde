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
 * Autoconfigured configuration details for a message submission agent.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Server_Msa extends Horde_Mail_Autoconfig_Server
{
    /**
     * Default MSA port (RFC 6409).
     */
    public $port = 587;

    /**
     */
    public function valid(array $opts = array())
    {
        if (empty($opts['users'])) {
            unset($opts['auth']);
            $opts['users'] = array(null);
        }

        $secure = $this->tls
            ? 'ssl'
            : (!empty($opts['insecure']) ?: 'tls');

        foreach ($opts['users'] as $user) {
            try {
                $smtp = new Horde_Smtp(array(
                    'host' => $this->host,
                    'password' => isset($opts['auth']) ? $opts['auth'] : null,
                    'port' => $this->port,
                    'secure' => $secure,
                    'timeout' => 2,
                    'username' => $user
                ));
                $smtp->noop();
                $smtp->shutdown();

                if (isset($opts['auth'])) {
                    $this->username = $user;
                }

                return true;
            } catch (Horde_Smtp_Exception $e) {}
        }

        return false;
    }

}
