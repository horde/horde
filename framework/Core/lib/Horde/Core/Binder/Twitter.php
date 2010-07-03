<?php
/**
 * Binder for creating Horde_Service_Twitter objects.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Core
 */
class Horde_Core_Binder_Twitter implements Horde_Injector_Binder
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
            $request = new Horde_Service_Twitter_Request_Oauth();
            $twitter = new Horde_Service_Twitter($auth, $request);

            //$twitter->setCache($injector->getInstance('Horde_Cache'));
            $twitter->setLogger($injector->getInstance('Horde_Log_Logger'));
            $twitter->setHttpClient($GLOBALS['injector']->getInstance('Horde_Http_Client'));

            return $twitter;
        } else {
            throw new Horde_Service_Twitter_Exception(_("No OAuth Key or Secret found for the Twitter API"));
        }
    }

    public function equals (Horde_Injector_Binder $binder)
    {
        return false;
    }
}