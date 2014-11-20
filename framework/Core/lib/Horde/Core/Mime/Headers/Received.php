<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This class represents the Received header value.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.17.0
 */
class Horde_Core_Mime_Headers_Received
    extends Horde_Mime_Headers_Received
{
    /**
     * Generate a 'Received' header for the Web browser -> Horde hop (conforms
     * to formatting guidelines in RFC 5321 [4.4]).
     */
    public static function createHordeHop()
    {
        global $conf, $registry;

        $remote = $registry->remoteHost();

        if (!empty($_SERVER['REMOTE_IDENT'])) {
            $remote_ident = $_SERVER['REMOTE_IDENT'] . '@' . $remote->host . ' ';
        } elseif ($remote->host != $remote->addr) {
            $remote_ident = $remote->host . ' ';
        } else {
            $remote_ident = '';
        }

        if (!empty($conf['server']['name'])) {
            $server_name = $conf['server']['name'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $server_name = $_SERVER['SERVER_NAME'];
        } elseif (!empty($_SERVER['HTTP_HOST'])) {
            $server_name = $_SERVER['HTTP_HOST'];
        } else {
            $server_name = 'unknown';
        }


        return new self(
            null,
            'from ' . $remote->host . ' (' . $remote_ident .
            '[' . $remote->addr . ']) ' .
            'by ' . $server_name . ' (Horde Framework) with HTTP; ' .
            date('r')
        );
    }

}
