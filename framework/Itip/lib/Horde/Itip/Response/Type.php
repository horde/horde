<?php
/**
 * Marks the response type.
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
 * Marks the response type.
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