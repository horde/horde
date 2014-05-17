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
 * Do simplistic guessing of hosts by appending common server names to domain.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Driver_Guess extends Horde_Mail_Autoconfig_Driver
{
    /**
     * DNS resolver.
     *
     * @var Net_DNS2_Resolver
     */
    public $dns;

    /**
     * Low priority: shot-in-the dark technique that has no formalized
     * standard.
     */
    public $priority = 30;

    /**
     */
    public function msaSearch($domains, array $opts = array())
    {
        $out = array();

        foreach ($domains as $val) {
            $tmp = new Horde_Mail_Autoconfig_Server_Msa();
            $tmp->host = $val;
            $out[] = $tmp;
        }

        foreach ($out as $val) {
            $tmp = clone $val;
            $tmp->host = 'smtp.' . $tmp->host;
            $out[] = $tmp;

            $tmp = clone $val;
            $tmp->host = 'mail.' . $tmp->host;
            $out[] = $tmp;
        }

        return $this->_resolveHosts($out);
    }

    /**
     */
    public function mailSearch($domains, array $opts = array())
    {
        $out = array();

        foreach ($domains as $val) {
            $tmp = new Horde_Mail_Autoconfig_Server_Imap();
            $tmp->host = $val;
            $out[] = $tmp;

            $tmp = new Horde_Mail_Autoconfig_Server_Pop3();
            $tmp->host = $val;
            $out[] = $tmp;
        }

        foreach ($out as $val) {
            if (empty($opts['no_imap']) &&
                ($val instanceof Horde_Mail_Autoconfig_Server_Imap)) {
                $tmp = clone $val;
                $tmp->host = 'imap.' . $tmp->host;
                $out[] = $tmp;
            }
            if (empty($opts['no_pop3']) &&
                ($val instanceof Horde_Mail_Autoconfig_Server_Pop3)) {
                $tmp = clone $val;
                $tmp->host = 'pop.' . $tmp->host;
                $out[] = $tmp;

                $tmp = clone $val;
                $tmp->host = 'pop3.' . $tmp->host;
                $out[] = $tmp;
            }

            $tmp = clone $val;
            $tmp->host = 'mail.' . $tmp->host;
            $out[] = $tmp;
        }

        return $this->_resolveHosts($out);
    }

    /**
     * Filter list by removing non-existent hosts.
     *
     * @param array $hosts  List of possible servers (objects).
     *
     * @return mixed  Array of existing hosts, or false if none exist.
     */
    protected function _resolveHosts($hosts)
    {
        $out = array();

        if (is_null($this->dns)) {
            $this->dns = new Net_DNS2_Resolver();
        }

        foreach ($hosts as $val) {
            try {
                $this->dns->query($val->host, 'A');
                $out[] = $val;
            } catch (Net_DNS2_Exception $e) {
                // Not found; ignore.
            }
        }

        return count($out) ? $out : false;
    }

}
