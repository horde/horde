<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * Constants
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */
class Horde_Http
{
    /**
     * Authentication schemes
     */
    const AUTH_ANY = 'ANY';
    const AUTH_BASIC = 'BASIC';
    const AUTH_DIGEST = 'DIGEST';
    const AUTH_NTLM = 'NTLM';
    const AUTH_GSSNEGOTIATE = 'GSSNEGOTIATE';

    /**
     * Proxy types
     */
    const PROXY_HTTP = 0;
    const PROXY_SOCKS4 = 1;
    const PROXY_SOCKS5 = 2;
}
