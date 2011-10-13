<?php
/**
 * This class represents the Kolab user database behind the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class represents the Kolab user database behind the free/busy system.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_UserDb_Kolab
extends Horde_Kolab_FreeBusy_UserDb_Kolab
{
    /**
     * Fetch an owner representation from the user database.
     *
     * @param string $owner  The owner name.
     * @param array  $params Additonal parameters.
     *
     * @return Horde_Kolab_FreeBusy_Owner The owner representation.
     */
    public function getOwner($owner, $params = array())
    {
        return new Horde_Kolab_FreeBusy_Freebusy_Owner_Kolab(
            $owner, $this->_db, $params
        );
    }
}
