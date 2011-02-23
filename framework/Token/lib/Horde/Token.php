<?php
/**
 * The Horde_Token:: class provides a common abstracted interface into the
 * various token generation mediums. It also includes all of the
 * functions for retrieving, storing, and checking tokens.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Max Kalika <max@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Token
 */
class Horde_Token
{
    /**
     * Generates a connection id and returns it.
     *
     * @param string $seed  A unique ID to be included in the token.
     *
     * @return string  The generated id string.
     */
    static public function generateId($seed = '')
    {
        return Horde_Url::uriB64Encode(pack('H*', hash('sha1', uniqid(mt_rand()) . $seed . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''))));
    }

}
