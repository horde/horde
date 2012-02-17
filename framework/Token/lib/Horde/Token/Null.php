<?php
/**
 * The Horde_Token_Null:: class provides a null implementation of the token
 * driver.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Token
 */
class Horde_Token_Null extends Horde_Token_Base
{
    /**
     * Does the token exist?
     *
     * @return boolean  True if the token exists.
     */
    public function exists($tokenID)
    {
        return false;
    }

    /**
     * Add a token ID.
     *
     * @param string $tokenID  Token ID to add.
     */
    public function add($tokenID)
    {
    }

    /**
     * Delete all expired connection IDs.
     */
    public function purge()
    {
    }
}
