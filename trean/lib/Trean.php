<?php
/**
 * Trean Base Class.
 *
 * $Horde: trean/lib/Trean.php,v 1.93 2009-11-29 15:51:42 chuck Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Trean
 */
class Trean
{
    /**
     */
    function sortOrder($sortby)
    {
        switch ($sortby) {
        case 'title':
            return 0;

        case 'rating':
        case 'clicks':
            return 1;
        }
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission, currently only 'max_folders'
     *                            and 'max_bookmarks'.
     *
     * @return mixed  The value of the specified permission.
     */
    function hasPermission($permission)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('trean:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('trean:' . $permission, $GLOBALS['registry']->getAuth());
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_folders':
            case 'max_bookmarks':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
    }

    /**
     * Returns the "Reason Phrase" associated with the given HTTP status code
     * according to rfc2616.
     */
    function HTTPStatus($status_code)
    {
        switch ($status_code) {
        case '100': return _("Continue");
        case '101': return _("Switching Protocols");
        case '200': return _("OK");
        case '201': return _("Created");
        case '202': return _("Accepted");
        case '203': return _("Non-Authoritative Information");
        case '204': return _("No Content");
        case '205': return _("Reset Content");
        case '206': return _("Partial Content");
        case '300': return _("Multiple Choices");
        case '301': return _("Moved Permanently");
        case '302': return _("Found");
        case '303': return _("See Other");
        case '304': return _("Not Modified");
        case '305': return _("Use Proxy");
        case '307': return _("Temporary Redirect");
        case '400': return _("Bad Request");
        case '401': return _("Unauthorized");
        case '402': return _("Payment Required");
        case '403': return _("Forbidden");
        case '404': return _("Not Found");
        case '405': return _("Method Not Allowed");
        case '406': return _("Not Acceptable");
        case '407': return _("Proxy Authentication Required");
        case '408': return _("Request Time-out");
        case '409': return _("Conflict");
        case '410': return _("Gone");
        case '411': return _("Length Required");
        case '412': return _("Precondition Failed");
        case '413': return _("Request Entity Too Large");
        case '414': return _("Request-URI Too Large");
        case '415': return _("Unsupported Media Type");
        case '416': return _("Requested range not satisfiable");
        case '417': return _("Expectation Failed");
        case '500': return _("Internal Server Error");
        case '501': return _("Not Implemented");
        case '502': return _("Bad Gateway");
        case '503': return _("Service Unavailable");
        case '504': return _("Gateway Time-out");
        case '505': return _("HTTP Version not supported");
        default: return '';
        }
    }

    /**
     * Returns an apropriate icon for the given bookmark.
     */
    function getFavicon($bookmark)
    {
        global $registry;

        // Initialize VFS.
        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
            if ($bookmark->favicon
                && $vfs->exists('.horde/trean/favicons/', $bookmark->favicon)) {
                return Horde_Util::addParameter(Horde::url('favicon.php'),
                                                'bookmark_id', $bookmark->id);
            }
        } catch (Exception $e) {
        }

        // Default to the protocol icon.
        $protocol = substr($bookmark->url, 0, strpos($bookmark->url, '://'));
        return Horde_Themes::img('/protocol/' . (empty($protocol) ? 'http' : $protocol) . '.png');
    }
}
