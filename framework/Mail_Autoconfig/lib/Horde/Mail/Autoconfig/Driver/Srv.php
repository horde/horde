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
 * Perform RFC 6186 DNS SRV record lookups to determine mail configuration.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Driver_Srv extends Horde_Mail_Autoconfig_Driver
{
    /**
     * DNS resolver.
     *
     * @var Net_DNS2_Resolver
     */
    public $dns;

    /**
     * High priority: this is a standardized (RFC) method of determining
     * configuration values.
     */
    public $priority = 10;

    /**
     */
    public function msaSearch($domains, array $opts = array())
    {
        $queries = array('_submission');
        return $this->_srvSearch($domains, $queries);
    }

    /**
     */
    public function mailSearch($domains, array $opts = array())
    {
        $queries = array();
        if (empty($opts['no_imap'])) {
            $queries[] = '_imap';
            $queries[] = '_imaps';
        }
        if (empty($opts['no_pop3'])) {
            $queries[] = '_pop3';
            $queries[] = '_pop3s';
        }

        return $this->_srvSearch($domains, $queries);
    }

    /**
     * Perform the SRV search.
     *
     * @param array $domains  List of domains to search.
     * @param array $queries  The SRV queries to run.
     *
     * @return mixed  False if no servers found, or a list of server objects
     *                in order of decreasing priority.
     */
    protected function _srvSearch($domains, $queries)
    {
        $obs = $out = array();

        if (is_null($this->dns)) {
            $this->dns = new Net_DNS2_Resolver();
        }

        foreach ($domains as $val) {
            foreach ($queries as $val2) {
                try {
                    $res = $this->dns->query($val2 . '._tcp.' . $val, 'SRV');
                    foreach ($res->answer as $val3) {
                        if (strlen($val3->target)) {
                            $val3->query = $val2;
                            $obs[$val3->priority][] = $val3;
                        }
                    }
                } catch (Net_DNS2_Exception $e) {
                    // Not found; ignore.
                }
            }
        }

        if (empty($obs)) {
            return false;
        }

        /* Sort via priority ranking. Lower value is higher priority. */
        ksort($obs, SORT_NUMERIC);

        foreach ($obs as $val) {
            /* Do weight determination if a multiple servers have identical
             * priorities. */
            if (count($val) > 1) {
                /* Weight determination algorithm is defined in RFC 2782.
                 * First, move all entries with weight 0 to beginning of
                 * list. */
                $tmp = array();
                foreach ($val as $key2 => $val2) {
                    if (empty($val2->weight)) {
                        $tmp[] = $val2;
                        unset($val[$key2]);
                    }
                }
                $tmp = array_merge($tmp, $val);

                $val = array();

                while (count($tmp) > 1) {
                    $i = 0;

                    /* Next, iterate over list and update the "running
                     * sum": the incremental value of each entry's weight. */
                    foreach ($tmp as $val2) {
                        $i += $val2->weight;
                        $val2->running = $i;
                    }

                    /* Finally, select a random number in the range of 0->$i.
                     * The first entry in the list (sequentially) that has a
                     * running total >= to this random number is the next
                     * server in the priority list. */
                    $rand = mt_rand(0, $i);
                    foreach ($tmp as $key2 => $val2) {
                        if ($val2->running >= $rand) {
                            $val[] = $val2;
                            /* Remove this server from the list. */
                            unset($tmp[$key2]);
                            break;
                        }
                    }

                    /* Repeat until we have a single entry left in $tmp. */
                }

                /* One entry left in $tmp, so add to $val. */
                $val[] = reset($tmp);
            }

            foreach ($val as $val2) {
                switch ($val2->query) {
                case '_imap':
                    $tmp = new Horde_Mail_Autoconfig_Server_Imap();
                    break;

                case '_imaps':
                    $tmp = new Horde_Mail_Autoconfig_Server_Imap();
                    $tmp->tls = 'tls';
                    break;

                case '_pop3':
                    $tmp = new Horde_Mail_Autoconfig_Server_Pop3();
                    break;

                case '_pop3s':
                    $tmp = new Horde_Mail_Autoconfig_Server_Pop3();
                    $tmp->tls = 'tls';
                    break;

                case '_submission':
                    $tmp = new Horde_Mail_Autoconfig_Server_Msa();
                    break;
                }

                $tmp->host = strval($val2->target);
                $tmp->port = intval($val2->port);

                $out[] = $tmp;
            }
        }

        return $out;
    }

}
