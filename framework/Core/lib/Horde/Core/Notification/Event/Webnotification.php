<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * A webnotification event.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */
class Horde_Core_Notification_Event_Webnotification
extends Horde_Notification_Event
{
    /**
     * Web notification display parameters.
     *
     * @var array
     */
    public $webnotify = array();

    /**
     * Create a webnotification event.
     *
     * @param string $title  Notification title.
     * @param array $opts    Additional options:
     *   - icon: (string) URL to icon to display.
     *   - text: (string) Extra content to display within notification.
     */
    public static function createEvent($title, array $opts = array())
    {
        $ob = new self($title, 'webnotification');
        $ob->webnotify = $opts;

        return $ob;
    }

}
