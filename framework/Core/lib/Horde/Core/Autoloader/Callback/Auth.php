<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Autoloader_Callback_Auth
{
    /**
     * TODO
     */
        static public function callback()
    {
        Horde_Auth::$dnsResolver = $GLOBALS['injector']->getInstance('Net_DNS_Resolver');
    }

}
