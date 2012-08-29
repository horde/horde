<?php
/**
 * A data object that represents only JSON notification data.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Response_Notifications extends Horde_Core_Ajax_Response
{
    /**
     */
    public function __construct()
    {
        parent::__construct(null, true);
    }

    /**
     * Prepare JSON data response object.
     *
     * Only return notification data.
     *
     * @return object  Data response object.
     */
    public function jsonData()
    {
        return $this->notifications;
    }

}
