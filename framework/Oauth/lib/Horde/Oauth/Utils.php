<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */

/**
 * OAuth utilities
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */
class Horde_Oauth_Utils
{
    public static function urlencodeRfc3986($string)
    {
        return str_replace(array('%7E', '+'),
                           array('~', '%2B'),
                           rawurlencode($string));
    }

}
