<?php
/**
 * Indicates a tentatively accepted invitation.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Indicates a tentatively accepted invitation.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.horde.org/licenses/lgpl21 LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Response_Type_Tentative
extends Horde_Itip_Response_Type_Base
{
    /**
     * Return the status of the response.
     *
     * @return string The status.
     */
    public function getStatus()
    {
        return 'TENTATIVE';
    }

    /**
     * Return the abbreviated subject of the response.
     *
     * @return string The short subject.
     */
    public function getShortSubject()
    {
        return Horde_Itip_Translation::t("Tentative");
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
            ? Horde_Itip_Translation::t("has tentatively accepted the update to the following event")
            : Horde_Itip_Translation::t("has tentatively accepted the invitation to the following event");
    }
}