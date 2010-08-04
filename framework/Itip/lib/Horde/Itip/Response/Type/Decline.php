<?php
/**
 * Indicates a declined invitation.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Indicates a declined invitation.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Itip_Response_Type_Decline
extends Horde_Itip_Response_Type_Base
{
    /**
     * Return the status of the response.
     *
     * @return string The status.
     */
    public function getStatus()
    {
        return 'DECLINED';
    }

    /**
     * Return the abbreviated subject of the response.
     *
     * @return string The short subject.
     */
    public function getShortSubject()
    {
        return _("Declined");
    }

    /**
     * Return the short message for the response.
     *
     * @param boolean $is_update Indicates if the request was an update.
     *
     * @return string The short message.
     */
    public function getShortMessage($is_update = false)
    {
        return $is_update
            ? _("has declined the update to the following event")
            : _("has declined the invitation to the following event");
    }
}