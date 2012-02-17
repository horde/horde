<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
 */

/**
 * OAuth HMAC-SHA1 signature method
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
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
