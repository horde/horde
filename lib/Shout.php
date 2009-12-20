<?php
/**
 * Shout:: defines an set of classes for the Shout application.
 *
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @version $Revision: 94 $
 * @since   Shout 0.1
 * @package Shout
 */
require_once SHOUT_BASE . "/config/defines.php";

class Shout
{
    var $applist = array();
    var $_applist_curapp = '';
    var $_applist_curfield = '';

    /**
     * Build Shout's list of menu items.
     *
     * @access public
     */
    function getMenu($returnType = 'object')
    {
        global $conf, $context, $section, $action;

        require_once 'Horde/Menu.php';

        $menu = new Horde_Menu(HORDE_MENU_MASK_ALL);
        $permprefix = "shout:contexts:$context";

        if (isset($context) && $section == "usermgr" &&
            Shout::checkRights("$permprefix:users",
                PERMS_EDIT, 1)) {
            $url = Horde::applicationUrl("index.php");
            $url = Horde_Util::addParameter($url, array('context' => $context,
                                                  'section' => $section,
                                                  'action' => 'add'));

            # Goofy hack to make the icon make a little more sense
            # when editing/deleting users
//             if (!isset($action)) {
                $icontitle = "Add";
//             } else {
//                 $icontitle = $action;
//                 $icontitle[0] = strtoupper($action[0]);
//             }
            # End goofy hack

            $menu->add($url, _("$icontitle User"), "add-user.gif");
        }

        if (isset($context) && isset($section) && $section == "dialplan" &&
            Shout::checkRights("$permprefix:dialplan",
                PERMS_EDIT, 1)) {
            $url = Horde::applicationUrl("dialplan.php");
            $url = Horde_Util::addParameter($url, array('context' => $context,
                                                  'section' => $section,
                                                  'action' => 'add'));

            # Goofy hack to make the icon make a little sense
            # when editing/deleting users
            if (!isset($action)) {
                $icontitle = "Add";
            } else {
                $icontitle = $action;
                $icontitle[0] = strtoupper($action[0]);
            }
            # End goofy hack

            $menu->add($url, _("$icontitle Extension"), "add-extension.gif");
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Generate the tabs at the top of each Shout pages
     *
     * @param &$vars Reference to the passed in variables
     *
     * @return object Horde_UI_Tabs
     */
    function getTabs($context, &$vars)
    {
        global $shout;
        $perms = Horde_Perms::singleton();

        $permprefix = 'shout:contexts:' . $context;

        $tabs = new Horde_UI_Tabs('section', $vars);

        if (Shout::checkRights($permprefix . ':users', null, 1) &&
            $shout->checkContextType($context, 'users')) {

            $url = Horde::applicationUrl('usermgr.php');
            $url = Horde_Util::addParameter($url, 'context', $context);
            $tabs->addTab(_("_User Manager"), $url, 'usermgr');
        }

        if (Shout::checkRights($permprefix . ':dialplan', null, 1) &&
            $shout->checkContextType($context, 'dialplan')) {

            $url = Horde::applicationUrl('dialplan.php');
            $url = Horde_Util::addParameter($url, 'context', $context);
            $tabs->addTab(_("_Dial Plan"), $url, 'dialplan');
        }

        if (Shout::checkRights($permprefix . ':conference', null, 1) &&
            $shout->checkContextType($context, 'conference')) {

            $url = Horde::applicationUrl('conference.php');
            $url = Horde_Util::addParameter($url, 'context', $context);
            $tabs->addTab(_("_Conference Rooms"), $url, 'conference');
        }

       if (Shout::checkRights($permprefix . ':moh', null, 1) &&
            $shout->checkContextType($context, "moh")) {

            $url = Horde::applicationUrl('moh.php');
            $url = Horde_Util::addParameter($url, 'context', $context);
            $tabs->addTab(_("_Music on Hold"), $url, 'moh');
        }

        if ($perms->hasPermission('shout:superadmin', Horde_Auth::getAuth(), PERMS_SHOW|PERMS_READ)) {
            $url = Horde::applicationUrl('security.php');
            $url = Horde_Util::addParameter($url, 'context', $context);
            $tabs->addTab(_("_Security"), $url, 'security');
        }

        return $tabs;
    }

    /**
     * Checks for the given permissions for the current user on the given
     * permission.  Optionally check for higher-level permissions and ultimately
     * test for superadmin priveleges.
     *
     * @param string $permname Name of the permission to check
     *
     * @param optional int $permmask Bitfield of permissions to check for
     *
     * @param options int $numparents Check for the same permissions this
     *                                many levels up the tree
     *
     * @return boolean the effective permissions for the user.
     */
    function checkRights($permname, $permmask = null, $numparents = 0)
    {
        if (Horde_Auth::isAdmin()) { return true; }

        $perms = Horde_Perms::singleton();
        if ($permmask === null) {
            $permmask = PERMS_SHOW|PERMS_READ;
        }

        # Default deny all permissions
        $user = 0;
        $superadmin = 0;

        $superadmin = $perms->hasPermission('shout:superadmin',
            Horde_Auth::getAuth(), $permmask);

        while ($numparents >= 0) {
            $tmpuser = $perms->hasPermission($permname,
                Horde_Auth::getAuth(), $permmask);

            $user = $user | $tmpuser;
            if ($numparents > 0) {
                $pos = strrpos($permname, ':');
                if ($pos) {
                    $permname = substr($permname, 0, $pos);
                }
            }
            $numparents--;
        }
        $test = $superadmin | $user;
$ret = ($test & $permmask) == $permmask;
print "Shout::checkRights() returning $ret";
        return ($test & $permmask) == $permmask;
    }

    function getContextTypes()
    {
        return array(SHOUT_CONTEXT_CUSTOMERS => _("Customers"),
                     SHOUT_CONTEXT_EXTENSIONS => _("Dialplan"),
                     SHOUT_CONTEXT_MOH => _("Music On Hold"),
                     SHOUT_CONTEXT_CONFERENCE => _("Conference Calls"));
    }

    /**
     * Given an integer value of permissions returns an array
     * representation of the integer.
     *
     * @param integer $int  The integer representation of permissions.
     */
    function integerToArray($int)
    {
        static $array = array();
        if (isset($array[$int])) {
            return $array[$int];
        }

        $array[$int] = array();

        /* Get the available perms array. */
        $types = Shout::getContextTypes();

        /* Loop through each perm and check if its value is included in the
         * integer representation. */
        foreach ($types as $val => $label) {
            if ($int & $val) {
                $array[$int][$val] = true;
            }
        }

        return $array[$int];
    }

    /**
     * Convert Asterisk's special extensions to friendly names
     *
     * @param string $extension  Extension to search for friendly name.
     */
    function exten2name($exten)
    {
        # Cast as a string to avoid misinterpreted digits
        switch((string)$exten) {
        case 'i':
            $nodetext = 'Invalid Handler';
            break;
        case 's':
            $nodetext = 'Entry Point';
            break;
        case 't':
            $nodetext = 'Timeout Handler';
            break;
        case 'o':
            $nodetext = 'Operator';
            break;
        case 'h':
            $nodetext = 'Hangup Handler';
            break;
        case 'fax':
            $nodetext = 'FAX Detection';
            break;
        default:
            $nodetext = "Extension $exten";
            break;
        }

        return $nodetext;
    }

    /**
     * Compare two inputs as extensions and return them in the following order:
     * 's', numbers (low to high), single chars, multi-chars
     * 's' comes first because in Asterisk it is commonly the 'starting' exten.
     * This function is expected to be used with uksort()
     *
     * @param string $e1
     *
     * @param string $e2
     *
     * @return int Relation of $e1 to $e2
     */
    function extensort($e1, $e2)
    {
        # Assumptions: We don't have to deal with negative numbers.  If we do
        # they'll sort as strings
        $e1 = (string)$e1;
        $e2 = (string)$e2;
        # Try to return quickly if either extension is 's'
        if ($e1 == 's' || $e2 == 's') {
            if ($e1 == $e2) {
                # They are both s?
                # FIXME Should we warn here?  Or assume the rest of the app
                # is smart enough to handle this condition?
                return 0;
            }

            return ($e1 == 's') ? -1 : 1;
        }

        # Next check for numeric extensions
        if (preg_match('/^[*#0-9]+$/', $e1)) {
            # e1 is a numeric extension
            if (preg_match('/^[*#0-9]+$/', $e2)) {
                # e2 is also numeric
                if (strlen($e1) == 1 || strlen($e2) == 1) {
                    if (strlen($e1) == strlen($e2)) {
                        # Both are 1 digit long
                        return ($e1 < $e2) ? -1 : 1;
                    } else {
                        return (strlen($e1) == 1) ? -1 : 1;
                    }
                }
                return ($e1 < $e2) ? -1 : 1;
            } else {
                # e2 is not a numeric extension so it must sort after e1
                return -1;
            }
        } elseif (preg_match('/^[*#0-9]+$/', $e2)) {
            # e2 is numeric but e1 is not.  e2 must sort before e1
            return 1;
        }

        # e1 and e2 are both strings
        if (strlen($e1) == 1 || strlen($e2) == 1) {
            # e1 or e2 is a single char extension (reserved in Asterisk)
            return (strlen($e1) == 1) ? -1 : 1;
        } else {
            # e1 and e2 are both multi-char strings.  Sort them equally.
            # FIXME Should add logic to make one multi-char take precedence
            # over another?
            return 0;
        }
    }

    function getApplist()
    {
        if (isset($_SESSION['shout']['applist'])) {
            return $_SESSION['shout']['applist'];
        }

        $file = SHOUT_BASE . '/config/applist.xml';

        $xml_parser = xml_parser_create();
        $ShoutObject = new Shout;
        xml_set_element_handler($xml_parser,
            array(&$ShoutObject, '_xml2applist_startElement'),
            array(&$ShoutObject, '_xml2applist_startElement'));
        xml_set_character_data_handler($xml_parser,
            array(&$ShoutObject, '_xml2applist_characterData'));

        if (!$fp = fopen($file, 'r')) {
            return PEAR::raiseError('Unable to open applist.xml for reading');
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                return PEAR::raiseError(sprintf("Invalid XML %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)));
            }
        }
        ksort($ShoutObject->applist);
        xml_parser_free($xml_parser);
        $_SESSION['shout']['applist'] = $ShoutObject->applist;
        unset($ShoutObject);
        return $_SESSION['shout']['applist'];
    }

    function _xml2applist_startElement($parser, $name, $attrs = array())
    {
        if (count($attrs) > 1) { print_r($attrs); }
        switch($name) {
        case 'APPLICATION':
            if (isset($attrs['NAME'])) {
                $this->_applist_curapp = $attrs['NAME'];
                if (!isset($this->applist[$name])) {
                    $this->applist[$this->_applist_curapp] = array();
                }
                $this->_applist_curfield = '';
            }
            break;
        case 'SYNOPSIS':
        case 'USAGE':
            $this->_applist_curfield = $name;
            if (!isset($this->applist[$name])) {
                $this->applist[$this->_applist_curapp][$name] = "";
            }
            break;
        }
    }

    function _xml2applist_endElement($parser, $name)
    {
        print ''; #NOOP
    }

    function _xml2applist_characterData($parser, $string)
    {
        $string = preg_replace('/^\s+/', '', $string);
        $string = preg_replace('/\s+$/', '', $string);
        if (strlen($string) > 1) {
            $field = $this->_applist_curfield;
            $app = $this->_applist_curapp;
            $this->applist[$app][$field] .= "$string ";
        }
    }
}
