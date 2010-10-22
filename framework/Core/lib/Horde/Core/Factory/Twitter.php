<?php
/**
 * Binder for creating Horde_Service_Twitter objects.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_Twitter
{
    public function create(Horde_Injector $injector)
    {
        global $conf, $prefs;

        if (!empty($conf['twitter']['key']) && !empty($conf['twitter']['secret'])) {

            /* Keys - these are obtained when registering for the service */
            $consumer_key = $conf['twitter']['key'];
            $consumer_secret = $conf['twitter']['secret'];

           /* Parameters required for the Horde_Oauth_Consumer */
            $params = array(
                'key' => $consumer_key,
                'secret' => $consumer_secret,
                'requestTokenUrl' => Horde_Service_Twitter::REQUEST_TOKEN_URL,
                'authorizeTokenUrl' => Horde_Service_Twitter::USER_AUTHORIZE_URL,
                'accessTokenUrl' => Horde_Service_Twitter::ACCESS_TOKEN_URL,
                'signatureMethod' => new Horde_Oauth_SignatureMethod_HmacSha1()
            );

            /* Create the Consumer */
            $auth = new Horde_Service_Twitter_Auth_Oauth(new Horde_Oauth_Consumer($params));
            $request = new Horde_Service_Twitter_Request_Oauth($injector->getInstance('Horde_Controller_Request'));
            $twitter = new Horde_Service_Twitter($auth, $request);

            //$twitter->setCache($injector->getInstance('Horde_Cache'));
            $twitter->setLogger($injector->getInstance('Horde_Log_Logger'));
            $twitter->setHttpClient($injector->getInstance('Horde_Core_Factory_HttpClient')->create());

            return $twitter;
        } else {
            throw new Horde_Service_Twitter_Exception(Horde_Core_Translation::t("No OAuth Key or Secret found for the Twitter API"));
        }
    }
}
