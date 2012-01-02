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
 * OAuth abstract signature method base class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
 */
abstract class Horde_Oauth_SignatureMethod
{
    abstract public function getName();

    abstract public function sign($request, $consumer, $token);

    public function verify($signature, $request, $consumer, $token)
    {
        return $signature == $this->sign($request, $consumer, $token);
    }
}
