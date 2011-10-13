<?php
/**
 * Interface defining event status to free/busy status mappers.
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
 * Interface defining event status to free/busy status mappers.
 *
 * Copyright 2011 Kolab Systems AG
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
interface Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap
{
    /** Free/busy status codes */
    const STATUS_FREE             = 'FREE';
    const STATUS_BUSY             = 'BUSY';
    const STATUS_BUSY_UNAVAILABLE = 'BUSY-UNAVAILABLE';
    const STATUS_BUSY_TENTATIVE   = 'BUSY-TENTATIVE';

    /**
     * Map the event status to a free/busy status.
     *
     * @param string $status The event status.
     *
     * @return string The corresponding free/busy status.
     */
    public function map($status);
}