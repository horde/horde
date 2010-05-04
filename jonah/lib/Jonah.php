<?php
/**
 * @package Jonah
 */

/**
 * Internal Jonah channel.
 */
define('JONAH_INTERNAL_CHANNEL', 0);

/**
 * External channel.
 */
define('JONAH_EXTERNAL_CHANNEL', 1);

/**
 * Aggregated channel.
 */
define('JONAH_AGGREGATED_CHANNEL', 2);

/**
 * Composite channel.
 */
define('JONAH_COMPOSITE_CHANNEL', 3);

/**
 */
define('JONAH_ORDER_PUBLISHED', 0);
define('JONAH_ORDER_READ', 1);
define('JONAH_ORDER_COMMENTS', 2);


/**
 * Jonah Base Class.
 *
 * $Horde: jonah/lib/Jonah.php,v 1.141 2009/11/24 04:15:37 chuck Exp $
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Eric Rechlin <eric@hpcalc.org>
 * @package Jonah
 */
class Jonah {

    /**
     */
    function _readURL($url)
    {
        global $conf;

        $options['method'] = 'GET';
        $options['timeout'] = 5;
        $options['allowRedirects'] = true;

        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $conf['http']['proxy']);
        }

        require_once 'HTTP/Request.php';
        $http = new HTTP_Request($url, $options);
        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            return PEAR::raiseError(sprintf(_("Could not open %s."), $url));
        }

        $result = array('body' => $http->getResponseBody());
        $content_type = $http->getResponseHeader('Content-Type');
        if (preg_match('/.*;\s?charset="?([^"]*)/', $content_type, $match)) {
            $result['charset'] = $match[1];
        } elseif (preg_match('/<\?xml[^>]+encoding=["\']?([^"\'\s?]+)[^?].*?>/i', $result['body'], $match)) {
            $result['charset'] = $match[1];
        }

        return $result;
    }

    /**
     * Returns a drop-down select box to choose which view to display.
     *
     * @param name Name to assign to select box.
     * @param selected Currently selected item. (optional)
     * @param onchange JavaScript onchange code. (optional)
     *
     * @return string Generated select box code
     */
    function buildViewWidget($name, $selected = 'standard', $onchange = '')
    {
        require JONAH_BASE . '/config/templates.php';

        if ($onchange) {
            $onchange = ' onchange="' . $onchange . '"';
        }

        $html = '<select name="' . $name . '"' . $onchange . '>' . "\n";
        foreach ($templates as $key => $tinfo) {
            $select = ($selected == $key) ? ' selected="selected"' : '';
            $html .= '<option value="' . $key . '"' . $select . '>' . $tinfo['name'] . "</option>\n";
        }
        return $html . '</select>';
    }

    /**
     */
    function getChannelTypeLabel($type)
    {
        switch ($type) {
        case JONAH_INTERNAL_CHANNEL:
            return _("Local Feed");

        case JONAH_EXTERNAL_CHANNEL:
            return _("External Feed");

        case JONAH_AGGREGATED_CHANNEL:
            return _("Aggregated Feed");

        case JONAH_COMPOSITE_CHANNEL:
            return _("Composite Feed");
        }
    }

    /**
     */
    function checkPermissions($filter, $permission = Horde_Perms::READ, $in = null)
    {
        if (Horde_Auth::isAdmin('jonah:admin', $permission)) {
            if (empty($in)) {
                // Calls with no $in parameter are checking whether this user
                // has permission.  Since this user is an admin, they always
                // have permission.  If the $in parameter is an empty array,
                // the method is expected to return an array too.
                return is_array($in) ? array() : true;
            } else {
                return $in;
            }
        }

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        $out = array();

        switch ($filter) {
        case 'internal_channels':
        case 'external_channels':
            if (empty($in) || !$perms->exists('jonah:news:' . $filter . ':' . $in)) {
                return $perms->hasPermission('jonah:news:' . $filter, Horde_Auth::getAuth(), $permission);
            } elseif (!is_array($in)) {
                return $perms->hasPermission('jonah:news:' . $filter . ':' . $in, Horde_Auth::getAuth(), $permission);
            } else {
                foreach ($in as $key => $val) {
                    if ($perms->hasPermission('jonah:news:' . $filter . ':' . $val, Horde_Auth::getAuth(), $permission)) {
                        $out[$key] = $val;
                    }
                }
            }
            break;

        case 'channels':
            foreach ($in as $key => $val) {
                $perm_name = Jonah::typeToPermName($val['channel_type']);
                if ($perms->hasPermission('jonah:news:' . $perm_name,  Horde_Auth::getAuth(), $permission) ||
                    $perms->hasPermission('jonah:news:' . $perm_name . ':' . $val['channel_id'], Horde_Auth::getAuth(), $permission)) {
                    $out[$key] = $in[$key];
                }
            }
            break;

        default:
            return $perms->hasPermission($filter, Horde_Auth::getAuth(), Horde_Perms::EDIT);
        }

        return $out;
    }

    /**
     */
    function typeToPermName($type)
    {
        if ($type == JONAH_INTERNAL_CHANNEL) {
            return 'internal_channels';
        } elseif ($type == JONAH_EXTERNAL_CHANNEL) {
            return 'external_channels';
        }
    }

    /**
     * Returns an array of configured body types from Jonah's $conf array.
     *
     * @return array  An array of body types.
     */
    function getBodyTypes()
    {
        static $types = array();
        if (!empty($types)) {
            return $types;
        }

        if (in_array('richtext', $GLOBALS['conf']['news']['story_types'])) {
            $types['richtext'] = _("Rich Text");
        }

        /* Other than checking if text is enabled, it is inserted by default if
         * no other body type has been enabled in the config. */
        if (in_array('text', $GLOBALS['conf']['news']['story_types']) ||
            empty($types)) {
            $types['text'] = _("Text");
        }

        return $types;
    }

    /**
     * Tries to figure out a default body type. Used when none has been
     * specified and a types is needed to fall back on to.
     *
     * @return string  A default type.
     */
    function getDefaultBodyType()
    {
        $types = Jonah::getBodyTypes();
        if (isset($types['text'])) {
            return 'text';
        } elseif (isset($types['richtext'])) {
            return 'richtext';
        }
        /* The two most common body types have not been found, so just return
         * the first one that is in the array. */
        $tmp = array_keys($types);
        return array_shift($tmp);
    }

    /**
     * Build Jonah's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $registry, $conf;

        $menu = new Horde_Menu();

        /* If authorized, show admin links. */
        if (Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            $menu->addArray(array('url' => Horde::applicationUrl('channels/index.php'), 'text' => _("_Feeds"), 'icon' => 'jonah.png'));
        }
        foreach ($conf['news']['enable'] as $channel_type) {
            if (Jonah::checkPermissions($channel_type, Horde_Perms::EDIT)) {
                $menu->addArray(array('url' => Horde::applicationUrl('channels/edit.php'), 'text' => _("New Feed"), 'icon' => 'new.png'));
                break;
            }
        }
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $news = Jonah_News::factory();
            $channel = $news->getChannel($channel_id);
            if ($channel['channel_type'] == JONAH_INTERNAL_CHANNEL &&
                Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
                $menu->addArray(array('url' => Horde::applicationUrl('stories/edit.php?channel_id=' . (int)$channel_id), 'text' => _("_New Story"), 'icon' => 'new.png'));
            }
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
