<?php
/**
 * TODO
 */
class Horde_Core_Autoloader_Callback_Mime
{
    /**
     * TODO
     */
    public function callback()
    {
        Horde_Mime::$brokenRFC2231 = !empty($GLOBALS['conf']['mailformat']['brokenrfc2231']);
    }

}
