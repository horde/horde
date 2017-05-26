<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * Constants for Horde_Http.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
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
