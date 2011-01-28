<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * Constants
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
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
}
