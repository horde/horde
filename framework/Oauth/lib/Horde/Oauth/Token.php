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
 * OAuth access tokens and request tokens
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */
class Horde_Oauth_Token
{
    public $key;
    public $secret;

    /**
     * key = the token
     * secret = the token secret
     */
    function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Generate the basic string serialization of a token that a server would
     * respond to request_token and access_token calls with.
     */
    public function __toString()
    {
        return
            'oauth_token='.Horde_Oauth_Utils::urlencodeRfc3986($this->key).
            '&oauth_token_secret='.Horde_Oauth_Utils::urlencodeRfc3986($this->secret);
    }

    public static function fromString($string)
    {
        parse_str($string, $parts);
        return new self($parts['oauth_token'], $parts['oauth_token_secret']);
    }
}
