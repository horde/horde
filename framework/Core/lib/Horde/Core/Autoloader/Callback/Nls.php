<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Autoloader_Callback_Nls
{
    /**
     * TODO
     */
    public static function callback()
    {
        Horde_Nls::$dnsResolver = $GLOBALS['injector']->getInstance('Net_DNS2_Resolver');
    }
}
