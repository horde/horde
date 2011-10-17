<?php
/**
 * Creates the Horde_Push recipients.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push
 */

/**
 * Creates the Horde_Push recipients.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL-2.0). If you did
 * not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
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
        foreach ($conf['recipients'] as $recipient) {
            switch($recipient) {
            case 'twitter':
                $result[] = $this->_createTwitter($conf);
                break;
            case 'blogger':
                $result[] = $this->_createBlogger($conf);
                break;
            }
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

        $twitter->setHttpClient($this->_createHttpClient($conf));

        $auth_token = new Horde_Oauth_Token($conf['twitter']['token_key'], $conf['twitter']['token_secret']);
        $twitter->auth->setToken($auth_token);

        return new Horde_Push_Recipient_Twitter($twitter);
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
}