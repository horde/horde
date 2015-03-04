<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Interface to allow flagging a message based on address matches.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
interface IMP_Flag_Match_Address
{
    /**
     * Set flag by doing address matching.
     *
     * @param mixed $data  Either a list of addresses (Horde_Mail_Rfc822_List)
     *                     or the identity that matched the address list.
     *
     * @return boolean  True if the flag should be set. False if the flag
     *                  should never be set. Null if flag should not be set,
     *                  but could be set by other matching interfaces.
     */
    public function matchAddress($data);

}
