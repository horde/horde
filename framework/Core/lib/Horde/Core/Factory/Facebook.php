<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_Facebook extends Horde_Core_Factory_Injector
{
    /**
     *
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        if (empty($conf['facebook']['id']) || empty($conf['facebook']['secret'])) {
            throw new Horde_Exception('Unable to find required Facebook configuration.');
        }

        /* Facebook key and secret */
        $appId = $conf['facebook']['id'];
        $secret = $conf['facebook']['secret'];

        /* Create required objects */
        $context = array('http_client' => $injector->getInstance('Horde_Core_Factory_HttpClient')->create(),
                         'http_request' => $injector->getInstance('Horde_Controller_Request_Http'));

        $fb = new Horde_Service_Facebook($appId, $secret, $context);

        /* Check for facebook session */
        $fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));
        if (!empty($fbp['sid'])) {
            try {
                $fb->auth->setSession($fbp['sid']);
            } catch (Horde_Service_Facebook_Exception $e) {
                throw new Horde_Exception($e);
            }
        }

        return $fb;
    }

}
