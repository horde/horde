<?php
/**
 * Describes the driver interface of the freebusy export drivers.
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
 * Describes the driver interface of the freebusy export drivers.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
 * @since    Horde 3.2
 */
interface Horde_Kolab_FreeBusy_Driver_Freebusy_Interface
{
    /**
     * Trigger regeneration of exported data.
     *
     * @param array $params The parameters required to regenerate the freebusy
     *                      data.
     *
     * @return Horde_Kolab_FreeBusy_Driver_Result The freebusy data.
     */
    public function trigger(array $params);
}