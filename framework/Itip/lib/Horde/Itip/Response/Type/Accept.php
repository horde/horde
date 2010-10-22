<?php
/**
 * Indicates an accepted invitation.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Indicates an accepted invitation.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Response_Type_Accept
extends Horde_Itip_Response_Type_Base
{
    /**
     * Return the status of the response.
     *
     * @return string The status.
     */
    public function getStatus()
    {
        return 'ACCEPTED';
    }

    /**
     * Return the abbreviated subject of the response.
     *
     * @return string The short subject.
     */
    public function getShortSubject()
    {
        return Horde_Itip_Translation::t("Accepted");
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
            ? Horde_Itip_Translation::t("has accepted the update to the following event")
            : Horde_Itip_Translation::t("has accepted the invitation to the following event");
    }
}