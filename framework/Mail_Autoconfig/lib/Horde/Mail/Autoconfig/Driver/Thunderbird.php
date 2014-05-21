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
 * Perform configuration lookups based on services provided by
 * Mozilla/Thunderbird.
 *
 * See: https://wiki.mozilla.org/Thunderbird:Autoconfiguration
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
class Horde_Mail_Autoconfig_Driver_Thunderbird
extends Horde_Mail_Autoconfig_Driver
{
    /**
     * Http client.
     *
     * @var Horde_Http_Client
     */
    public $http;

    /**
     * URL of Mozilla ISPDB server lookup.
     *
     * @var string
     */
    public $ispdb = 'https://live.mozillamessaging.com/autoconfig/v1.1/';

    /**
     * Medium priority: not standardized (i.e. RFC), but API is actively
     * maintained by Mozilla.
     */
    public $priority = 20;

    /**
     */
    public function msaSearch($domains, array $opts = array())
    {
        foreach ($domains as $val) {
            $res = $this->_process(
                $val,
                'outgoingServer',
                array('smtp'),
                isset($opts['email']) ? $opts['email'] : null
            );
            if ($res) {
                return $res;
            }
        }

        return false;
    }

    /**
     */
    public function mailSearch($domains, array $opts = array())
    {
        $types = array();
        if (empty($opts['no_imap'])) {
            $types[] = 'imap';
        }
        if (empty($opts['no_pop3'])) {
            $types[] = 'pop3';
        }

        foreach ($domains as $val) {
            $res = $this->_process(
                $val,
                'incomingServer',
                $types,
                isset($opts['email']) ? $opts['email'] : null
            );
            if ($res) {
                return $res;
            }
        }

        return false;
    }

    /**
     * Process a Thunderbird autoconfig entry.
     *
     * @param string $domain                    Domain name.
     * @param string $tag                       XML tag to parse.
     * @param array $types                      List of $tag types to process.
     * @param Horde_Mail_Rfc822_Address $email  Username.
     */
    protected function _process($domain, $tag, $types, $email)
    {
        $out = array();
        $urls = array(
            'http://' . urlencode($domain) . '/.well-known/autoconfig/mail/config-v1.1.xml',
            $this->ispdb . urlencode($domain)
        );
        if (!is_null($email)) {
            array_unshift(
                $urls,
                'http://autoconfig.' . urlencode($domain) . '/mail/config-v1.1.xml?emailaddress=' . urlencode($email->bare_address)
            );
        }

        if (is_null($this->http)) {
            $this->http = new Horde_Http_Client();
        }

        foreach ($urls as $url) {
            try {
                $get = $this->http->get($url);
                if ($get->code == 404) {
                    continue;
                }

                try {
                    $xml = new SimpleXMLElement($get->getBody());
                } catch (Exception $e) {
                    // No valid XML; ignore
                    continue;
                }

                $label = strval($xml->emailProvider->displayName);
                foreach ($xml->emailProvider->{$tag} as $val) {
                    if (in_array($val['type'], $types)) {
                        switch ($val['type']) {
                        case 'imap':
                            $ob = new Horde_Mail_Autoconfig_Server_Imap();
                            break;

                        case 'pop3':
                            $ob = new Horde_Mail_Autoconfig_Server_Pop3();
                            break;

                        case 'smtp':
                            $ob = new Horde_Mail_Autoconfig_Server_Msa();
                            break;
                        }

                        $ob->host = strval($val->hostname);
                        $ob->port = intval(strval($val->port));
                        $ob->label = $label;
                        if (strcasecmp($val->socketType, 'SSL') === 0) {
                            $ob->tls = 'tls';
                        }

                        if (!is_null($email) &&
                            $email->valid &&
                            strlen($val->username)) {
                            $ob->username = str_replace(
                                array(
                                    '%EMAILADDRESS%',
                                    '%EMAILLOCALPART%'
                                ),
                                array(
                                    $email->bare_address,
                                    $email->mailbox
                                ),
                                $val->username
                            );
                        }

                        $out[] = $ob;
                    }
                }

                if (count($out)) {
                    return $out;
                }
            } catch (Horde_Http_Exception $e) {
                // Not found; ignore.
            }
        }

        return false;
    }

}
