<?php
/**
 * TODO
 */
class Horde_Core_Autoloader_Callback_Auth
{
    /**
     * TODO
     */
    public function callback()
    {
        Horde_Auth::$dnsResolver = $GLOBALS['injector']->getInstance('Net_DNS_Resolver');
    }

}
