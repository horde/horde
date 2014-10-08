<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Autoloader_Callback_Mime
{
    /**
     * TODO
     */
    public static function callback()
    {
        Horde_Mime::$brokenRFC2231 = true;
    }

}
