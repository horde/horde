<?php
/**
 * Allows to cache data from a free/busy resource.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Allows to cache data from a free/busy resource.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Event_Decorator_Mcache
extends Horde_Kolab_FreeBusy_Resource_Decorator_Mcache
implements Horde_Kolab_FreeBusy_Resource_Event
{
    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Resource_Interface $resource The decorated resource.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Resource_Event $resource
    ) {
        parent::__construct($resource);
    }

    /**
     * Lists all events in the given time range.
     *
     * @param Horde_Date $startDate Start of range date object.
     * @param Horde_Date $endDate   End of range data object.
     *
     * @return array Events in the given time range.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the events failed.
     */
    public function listEvents(Horde_Date $startDate, Horde_Date $endDate)
    {
        return $this->getResource()->listEvents($startDate, $endDate);
    }
}
