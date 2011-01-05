<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */

/**
 * OAuth HMAC-SHA1 signature method
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */
class Horde_Oauth_SignatureMethod_HmacSha1 extends Horde_Oauth_SignatureMethod
{
    public function getName()
    {
        return 'HMAC-SHA1';
    }

    public function sign($request, $consumer, $token)
    {
        $baseString = $request->getSignatureBaseString();

        $key_parts = array(
            $consumer->secret,
            ($token) ? $token->secret : ''
        );

        $key_parts = array_map(array('Horde_Oauth_Utils','urlencodeRfc3986'), $key_parts);
        $key = implode('&', $key_parts);

        return base64_encode(hash_hmac('sha1', $baseString, $key, true));
    }
}
