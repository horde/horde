<?php
/**
 * A data object that represents raw JSON data.
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
 * @since    2.0.0
 */
class Horde_Core_Ajax_Response_Raw extends Horde_Core_Ajax_Response
{
    /**
     * Don't add notification messages to raw data.
     */
    public function addNotifications()
    {
    }

    /**
     * Prepare JSON data response object.
     *
     * For raw data, we send back whatever is stored in the data property.
     *
     * @return object  Data response object.
     */
    public function jsonData()
    {
        return $this->data;
    }

}
