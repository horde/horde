<?php
/**
 * Marks the response type.
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
 * Marks the response type.
 *
 * Copyright 2010 Kolab Systems AG
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
interface Horde_Itip_Response_Type
{
    /**
     * Return the status of the response.
     *
     * @return string The status.
     */
    public function getStatus();

    /**
     * Return the core subject of the response.
     *
     * @return string The short subject.
     */
    public function getShortSubject();

    /**
     * Return the subject of the response without using the comment.
     *
     * @return string The subject.
     */
    public function getBriefSubject();

    /**
     * Return the subject of the response.
     *
     * @return string The subject.
     */
    public function getSubject();

    /**
     * Return an additional message for the response.
     *
     * @param boolean $is_update Indicates if the request was an update.
     *
     * @return string The message.
     */
    public function getMessage($is_update = false);
}