<?php
/**
 * Interface definition for the free/busy exporter.
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
 * Interface definition for the free/busy exporter.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
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
interface Horde_Kolab_FreeBusy_Export_Freebusy
{
    /**
     * Get the start timestamp for the export.
     *
     * @return Horde_Date The start timestamp for the export.
     */
    public function getStart();

    /**
     * Get the end timestamp for the export.
     *
     * @return Horde_Date The end timestamp for the export.
     */
    public function getEnd();

    /**
     * Get the name of the resource.
     *
     * @return string The name of the resource.
     */
    public function getResourceName();

    /**
     * Return the organizer mail for the export.
     *
     * @return string The organizer mail.
     */
    public function getOrganizerMail();

    /**
     * Return the organizer name for the export.
     *
     * @return string The organizer name.
     */
    public function getOrganizerName();

    /**
     * Return the timestamp for the export.
     *
     * @return string The timestamp.
     */
    public function getDateStamp();

    /**
     * Generates the free/busy export.
     *
     * @return Horde_iCalendar  The iCal object.
     */
    public function export();
}
