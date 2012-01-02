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
 * OAuth plaintext signature method
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
 */
class Horde_Oauth_SignatureMethod_Plaintext extends Horde_Oauth_SignatureMethod
{
    public function getName()
    {
        return 'PLAINTEXT';
    }

    public function sign($request, $consumer, $token)
    {
        $signature = array(
            Horde_Oauth_Utils::urlencodeRfc3986($consumer->secret),
        );

        if ($token) {
            array_push($signature, Horde_Oauth_Utils::urlencodeRfc3986($token->secret));
        } else {
            array_push($signature, '');
        }

        return Horde_Oauth_Utils::urlencodeRfc3986(implode('&', $signature));
    }
}
