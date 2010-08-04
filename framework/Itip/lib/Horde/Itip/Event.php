<?php
/**
 * Defines the event interface required for iTip-Handling / resource booking.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Defines the event interface required for iTip-Handling / resource booking.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
interface Horde_Itip_Event
{
    /**
     * Returns the event as vEvent.
     *
     * @return Horde_iCalendar_vevent The wrapped event.
     */
    public function getVevent();

    /**
     * Return the method of the iTip request.
     *
     * @return string The method of the request.
     */
    public function getMethod();

    /**
     * Return the uid of the iTip event.
     *
     * @return string The uid of the event.
     */
    public function getUid();

    /**
     * Return the summary for the event.
     *
     * @return string The summary.
     */
    public function getSummary();

    /**
     * Return the start of the iTip event.
     *
     * @return string The start of the event.
     */
    public function getStart();

    /**
     * Return the end of the iTip event.
     *
     * @return string The end of the event.
     */
    public function getEnd();

    /**
     * Return the organizer of the iTip event.
     *
     * @return string The organizer of the event.
     */
    public function getOrganizer();

    /**
     * Copy the details from an event into this one.
     *
     * @param Horde_Itip_Event $event The event to copy from.
     *
     * @return NULL
     */
    public function copyEventInto(Horde_Itip_Event $event);

    /**
     * Set the attendee parameters.
     *
     * @param string $attendee    The mail address of the attendee.
     * @param string $common_name Common name of the attendee.
     * @param string $status      Attendee status (ACCPETED, DECLINED, TENTATIVE)
     *
     * @return NULL
     */
    public function setAttendee($attendee, $common_name, $status);

    public function getKolabObject();

    public function setAccepted($resource);
}