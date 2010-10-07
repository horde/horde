<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_Facebook
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        if (empty($conf['facebook']['key']) || empty($conf['facebook']['secret'])) {
            throw new Horde_Exception('Unable to find required Facebook configuration.');
        }

        /* Facebook key and secret */
        $apikey = $conf['facebook']['key'];
        $secret = $conf['facebook']['secret'];

        /* Create required objects */
        $context = array('http_client' => $injector->getInstance('Horde_Core_Factory_HttpClient')->create(),
                         'http_request' => $injector->getInstance('Horde_Controller_Request_Http'));

        return new Horde_Service_Facebook($apikey, $secret, $context);
    }

}