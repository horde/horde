<?php
/**
 * The free/busy Kolab backend.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The free/busy Kolab backend.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Freebusy_Kolab
extends Horde_Kolab_FreeBusy_Resource_Kolab
implements Horde_Kolab_FreeBusy_Resource_Freebusy_Interface
{
    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Folder $folder The storage folder representing
     *                                           this resource.
     */
    public function __construct(Horde_Kolab_Storage_Folder $folder)
    {
        parent::__construct($folder);
        if ($folder->getType() != 'event') {
            throw new Horde_Kolab_FreeBusy_Exception(
                sprintf(
                    'Resource %s has type "%s" not "event"!',
                    $this->getName(), $folder->getType()
                )
            );
        }
    }

    /**
     * Return for whom this resource exports relevant data.
     *
     * @return string The user type the exported data of this resource is
     *                relevant for.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the relevance
     *                                        information failed.
     */
    public function getRelevance()
    {
        return $this->getFolder()->getKolabAttribute('incidences-for');
    }

    /**
     * Fetch the access controls on specific attributes of this
     * resource.
     *
     * @return array Attribute ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the attribute ACL
     *                                        information failed.
     */
    public function getAttributeAcl()
    {
        return $this->getFolder->getXfbaccess();
    }

    /**
     * Lists all events in the given time range.     *
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
        try {
            $objects = $this->_data->getObjects();
        } catch (Horde_Kolab_Storage_Exception $e) {
            //todo: prior exception
            throw new Horde_Kolab_FreeBusy_Exception($e);
        }
        $startts = $startDate->timestamp();
        $endts = $endDate->timestamp();

        $result = array();

        foreach($objects as $object) {
            /* check if event period intersects with given period */
            if (!(($object['start-date'] > $endts) ||
                  ($object['end-date'] < $startts))) {
                $event = new Kolab_Event($object);
                $result[] = $event;
                continue;
            }

            /* do recurrence expansion if not keeping anyway */
            if (isset($object['recurrence'])) {
                $event = new Kolab_Event($object);
                $next = $event->recurrence->nextRecurrence($startDate);
                while ($next !== false &&
                       $event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                    $next->mday++;
                    $next = $event->recurrence->nextRecurrence($next);
                }

                if ($next !== false) {
                    $duration = $next->timestamp() - $event->start->timestamp();
                    $next_end = new Horde_Date($event->end->timestamp() + $duration);

                    if ((!(($endDate->compareDateTime($next) < 0) ||
                           ($startDate->compareDateTime($next_end) > 0)))) {
                        $result[] = $event;
                    }

                }
            }
        }

        return $result;
    }
}
