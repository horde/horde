<?php
/**
 * This class implements an IMP system flag with matching on addresses.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
abstract class IMP_Flag_System_Match_Address extends IMP_Flag_Base
{
    /**
     * @param mixed $data  Either an array of addresses as returned by
     *                     Horde_Mime_Address::getAddressesFromObject() or the
     *                     identity that matched the address list.
     */
    public function match($data)
    {
        return false;
    }

}
