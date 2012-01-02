<?php
/**
 * Creates the Horde_Push recipients.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/components/Horde_Push
 */

/**
 * Creates the Horde_Push recipients.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/components/Horde_Push
 */
class Horde_Push_Factory_Recipients
{
    /**
     * Create the recipient list.
     *
     * @param array $options Command line options.
     * @param array $conf    The configuration.
     *
     * @return array The list of recipients.
     */
    public function create($options, $conf)
    {
        $result = array();

        if (isset($options['recipients'])) {
            $recipients = array_map('trim', explode(',', $options['recipients']));
        } elseif (isset($conf['recipients'])) {
            $recipients = $conf['recipients'];
        } else {
            return $result;
        }

        foreach ($recipients as $recipient) {
            if (strpos($recipient, ':') !== false) {
                list($recipient, $acl) = explode(':', $recipient);
            } else {
                $acl = null;
            }
            if (isset($conf['recipient'][$recipient])) {
                $type = $conf['recipient'][$recipient]['type'];
                if ($acl === null && isset($conf['recipient'][$recipient]['acl'])) {
                    $acl = $conf['recipient'][$recipient]['acl'];
                }
                $recipient_conf = array_merge($conf, $conf['recipient'][$recipient]);
            } else {
                $type = $recipient;
                $recipient_conf = $conf;
            }
            switch($type) {
            case 'twitter':
                $r = $this->_createTwitter($recipient_conf);
                break;
            case 'facebook':
                $r = $this->_createFacebook($recipient_conf);
                break;
            case 'blogger':
                $r = $this->_createBlogger($recipient_conf);
                break;
            case 'mail':
                $r = $this->_createMail($recipient_conf);
                break;
            case 'mock':
                $r = new Horde_Push_Recipient_Mock();
                break;
            default:
                throw new Horde_Push_Exception(
                    sprintf('Unknown recipient type "%s"!', $type)
                );
            }
            $r->setAcl($acl);
            $result[] = $r;
        }
        return $result;
    }

    /**
     * Create the twitter recipient.
     *
     * @param array $conf The configuration.
     * 
    * @return Horde_Push_Recipient_Twitter The twitter recipient.
     */
    private function _createTwitter($conf)
    {
        $params = array(
            'key' => $conf['twitter']['key'],
            'secret' => $conf['twitter']['secret'],
            'requestTokenUrl' => Horde_Service_Twitter::REQUEST_TOKEN_URL,
            'authorizeTokenUrl' => Horde_Service_Twitter::USER_AUTHORIZE_URL,
            'accessTokenUrl' => Horde_Service_Twitter::ACCESS_TOKEN_URL,
            'signatureMethod' => new Horde_Oauth_SignatureMethod_HmacSha1()
        );

        /* Create the Consumer */
        $auth = new Horde_Service_Twitter_Auth_Oauth(new Horde_Oauth_Consumer($params));
        $request = new Horde_Service_Twitter_Request_Oauth($this->_createControllerRequest($conf));
        $twitter = new Horde_Service_Twitter($auth, $request);

        $http = $this->_createHttpClient($conf);
        $twitter->setHttpClient($http);

        $auth_token = new Horde_Oauth_Token($conf['twitter']['token_key'], $conf['twitter']['token_secret']);
        $twitter->auth->setToken($auth_token);

        return new Horde_Push_Recipient_Twitter($twitter, $http);
    }

    /**
     * Create the facebook recipient.
     *
     * @param array $conf The configuration.
     * 
    * @return Horde_Push_Recipient_Facebook The facebook recipient.
     */
    private function _createFacebook($conf)
    {
        $facebook = new Horde_Service_Facebook(
            $conf['facebook']['key'],
            $conf['facebook']['secret'],
            array(
                'http_client' => $this->_createHttpClient($conf)
            )
        );
        $facebook->auth->setSession($conf['facebook']['sid']);
        return new Horde_Push_Recipient_Facebook($facebook, $conf['facebook']);
    }

    /**
     * Create the blogger recipient.
     *
     * @param array $conf The configuration.
     *
     * @return Horde_Push_Recipient_Blogger The blogger recipient.
     */
    private function _createBlogger($conf)
    {
        return new Horde_Push_Recipient_Blogger(
            $this->_createHttpClient($conf),
            $conf['blogger']
        );
    }

    /**
     * Create the mail recipient(s).
     *
     * @param array $conf The configuration.
     *
     * @return Horde_Push_Recipient_Mail The mail recipient(s).
     */
    private function _createMail($conf)
    {
        return new Horde_Push_Recipient_Mail(
            $this->_createMailTransport($conf),
            array('from' => isset($conf['mailer']['from']) ? $conf['mailer']['from'] : null)
        );
    }

    /**
     * Create a HTTP client.
     *
     * @param array $conf The configuration.
     *
     * @return Horde_Http_Client The HTTP client.
     */
    private function _createHttpClient($conf)
    {
        $client_opts = array();
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $client_opts['request.proxyServer'] = $conf['http']['proxy']['proxy_host'];
            $client_opts['request.proxyPort'] = $conf['http']['proxy']['proxy_port'];
            if (!empty($conf['http']['proxy']['proxy_user'])) {
                $client_opts['request.proxyUsername'] = $conf['http']['proxy']['proxy_user'];
                if (!empty($conf['http']['proxy']['proxy_pass'])) {
                    $client_opts['request.proxyPassword'] = $conf['http']['proxy']['proxy_pass'];
                }
            }
        }
        return new Horde_Http_Client($client_opts);
    }

    /**
     * Create a controller request.
     *
     * @param array $conf The configuration.
     *
     * @return Horde_Controller_Request The request representation.
     */
    private function _createControllerRequest($conf)
    {
        return new Horde_Controller_Request_Http();
    }

    /**
     * Create a mail transport.
     *
     * @param array $conf The configuration.
     *
     * @return Horde_Mail_Transport The mail transport.
     */
    private function _createMailTransport($conf)
    {
        $transport = isset($conf['mailer']['type'])
            ? $conf['mailer']['type']
            : 'null';
        $params = isset($conf['mailer']['params'])
            ? $conf['mailer']['params']
            : array();
        $class = 'Horde_Mail_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            return new $class($params);
        }
        throw new Horde_Push_Exception('Unable to find class for transport ' . $transport);
    }
}