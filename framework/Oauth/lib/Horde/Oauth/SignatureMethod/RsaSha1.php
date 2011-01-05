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
 * OAuth RSA-SHA1 signature method
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */
class Horde_Oauth_SignatureMethod_RsaSha1 extends Horde_Oauth_SignatureMethod_Base
{
    public function __construct($publicKey = null, $privateKey = null)
    {
        $this->_publicKey = $publicKey;
        $this->_privateKey = $privateKey;
    }

    public function getName()
    {
        return 'RSA-SHA1';
    }

    public function sign($request, $consumer, $token)
    {
        $baseString = $request->getSignatureBaseString();

        $pkeyid = openssl_pkey_get_private($this->_privateKey);
        $ok = openssl_sign($baseString, $signature, $pkeyid);
        openssl_free_key($pkeyid);

        return base64_encode($signature);
    }

    public function verify($signature, $request, $consumer, $token)
    {
        $decodedSignature = base64_decode($signature);
        $baseString = $request->getSignatureBaseString();

        $pubkeyid = openssl_pkey_get_public($this->_publicKey);
        $result = openssl_verify($baseString, $decodedSignature, $pubkeyid);
        openssl_free_key($pubkeyid);

        return $result == 1;
    }
}
