<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

/**
 * The virtual path to use for VFS data.
 */
define('WHUPS_VFS_ATTACH_PATH', '.horde/whups/attachments');

/**
 * The Whups:: class provides functionality that all of Whups needs,
 * or that should be encapsulated from other parts of the Whups
 * system.
 *
 * @package Whups
 */
class Whups {

    function urlFor($controller, $data, $full = false, $append_session = 0)
    {
        $rewrite = isset($GLOBALS['conf']['urls']['pretty']) &&
            $GLOBALS['conf']['urls']['pretty'] == 'rewrite';

        switch ($controller) {
        case 'queue':
            if ($rewrite) {
                if (is_array($data)) {
                    if (isset($data['slug'])) {
                        $slug = $data['slug'];
                    } else {
                        $slug = $data['id'];
                    }
                } else {
                    $slug = (int)$data;
                }
                return Horde::applicationUrl('queue/' . $slug, $full, $append_session);
            } else {
                if (is_array($data)) {
                    $id = $data['id'];
                } else {
                    $id = $data;
                }
                return Horde::applicationUrl('queue/?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket':
            $id = (int)$data;
            if ($rewrite) {
                return Horde::applicationUrl('ticket/' . $id, $full, $append_session);
            } else {
                return Horde::applicationUrl('ticket/?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket_rss':
            $id = (int)$data;
            if ($rewrite) {
                return Horde::applicationUrl('ticket/' . $id . '/rss', $full, $append_session);
            } else {
                return Horde::applicationUrl('ticket/rss.php?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket_action':
            list($controller, $id) = $data;
            if ($rewrite) {
                return Horde::applicationUrl('ticket/' . $id . '/' . $controller, $full, $append_session = 0);
            } else {
                return Horde::applicationUrl('ticket/' . $controller . '.php?id=' . $id, $full, $append_session = 0);
            }

        case 'query':
        case 'query_rss':
            if ($rewrite) {
                if (is_array($data)) {
                    if (isset($data['slug'])) {
                        $slug = $data['slug'];
                    } else {
                        $slug = $data['id'];
                    }
                } else {
                    $slug = (int)$data;
                }
                $url = 'query/' . $slug;
                if ($controller == 'query_rss') {
                    $url .= '/rss';
                }
                return Horde::applicationUrl($url, $full, $append_session);
            } else {
                if (is_array($data)) {
                    if (isset($data['slug'])) {
                        $param = array('slug' => $data['slug']);
                    } else {
                        $param = array('query' => $data['id']);
                    }
                } else {
                    $param = array('query' => $data);
                }
                $url = $controller == 'query' ? 'query/run.php' : 'query/rss.php';
                $url = Horde_Util::addParameter($url, $param);
                return Horde::applicationUrl($url, $full, $append_session);
            }
            break;
        }
    }

    function sortTickets(&$tickets, $by = null, $dir = null)
    {
        if (is_null($by)) {
            $by = $GLOBALS['prefs']->getValue('sortby');
        }
        if (is_null($dir)) {
            $dir = $GLOBALS['prefs']->getValue('sortdir');
        }

        Whups::sortBy($by);
        Whups::sortDir($dir);

        usort($tickets, array('Whups', '_sort'));
    }

    function sortBy($b = null)
    {
        static $by;

        if (!is_null($b)) {
            $by = $b;
        } else {
            return $by;
        }
    }

    function sortDir($d = null)
    {
        static $dir;

        if (!is_null($d)) {
            $dir = $d;
        } else {
            return $dir;
        }
    }

    function _sort($a, $b, $sortby = null, $sortdir = null)
    {
        static $by, $dir;
        if (is_null($by)) {
            $by = Whups::sortBy();
            $dir = Whups::sortDir();
        }

        if (is_null($sortby)) {
            $sortby = $by;
        }
        if (is_null($sortdir)) {
            $sortdir = $dir;
        }

        if (is_array($sortby)) {
            if (!isset($a[$sortby[0]])) {
                $a[$sortby[0]] = null;
            }
            if (!isset($b[$sortby[0]])) {
                $b[$sortby[0]] = null;
            }

            if (!count($sortby)) {
                return 0;
            } elseif ($a[$sortby[0]] > $b[$sortby[0]]) {
                return $sortdir[0] ? -1 : 1;
            } elseif ($a[$sortby[0]] === $b[$sortby[0]]) {
                array_shift($sortby);
                array_shift($sortdir);
                return Whups::_sort($a, $b, $sortby, $sortdir);
            } else {
                return $sortdir[0] ? 1 : -1;
            }
        } else {
            $a_val = isset($a[$sortby]) ? $a[$sortby] : null;
            $b_val = isset($b[$sortby]) ? $b[$sortby] : null;

            // Take care of the simplest case first
            if ($a_val === $b_val) {
                return 0;
            }

            if ((is_numeric($a_val) || is_null($a_val)) &&
                (is_numeric($b_val) || is_null($b_val))) {
                // Numeric comparison
                if ($sortdir) {
                    return (int)($b_val > $a_val);
                } else {
                    return (int)($a_val > $b_val);
                }
            } else {
                // String comparison
                if ($sortdir) {
                    return strcoll($b[$sortby], $a[$sortby]);
                } else {
                    return strcoll($a[$sortby], $b[$sortby]);
                }
            }
        }
    }

    /**
     * Returns a new or the current CAPTCHA string.
     *
     * @param boolean $new  If true, a new CAPTCHA is created and returned.
     *                      The current, to-be-confirmed string otherwise.
     *
     * @return string  A CAPTCHA string.
     */
    function getCAPTCHA($new = false)
    {
        if ($new || empty($_SESSION['whups']['CAPTCHA'])) {
            $_SESSION['whups']['CAPTCHA'] = '';
            for ($i = 0; $i < 5; $i++) {
                $_SESSION['whups']['CAPTCHA'] .= chr(rand(65, 90));
            }
        }
        return $_SESSION['whups']['CAPTCHA'];
    }

    /**
     * List all templates of a given type.
     *
     * @param string $type  The kind of template ('searchresults', etc.) to list.
     *
     * @return array  All templates of the requested type.
     */
    function listTemplates($type)
    {
        $templates = array();

        require WHUPS_BASE . '/config/templates.php';
        foreach ($_templates as $name => $info) {
            if ($info['type'] == $type) {
                $templates[$name] = $info['name'];
            }
        }

        return $templates;
    }

    /**
     * Get the current ticket - use the 'id' request variable to
     * determine what to look for. Will redirect to the default view
     * if the ticket isn't found or if permissions checks fail.
     */
    function getCurrentTicket()
    {
        $id = preg_replace('|\D|', '', Horde_Util::getFormData('id'));
        if (!$id) {
            $GLOBALS['notification']->push(_("Invalid Ticket Id"), 'horde.error');
            Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true)
                ->redirect();
        }

        $ticket = Whups_Ticket::makeTicket($id);
        if (is_a($ticket, 'PEAR_Error')) {
            if ($ticket->code === 0) {
                // No permissions to this ticket.
                $GLOBALS['notification']->push($ticket->getMessage(), 'horde.warning');
            } else {
                $GLOBALS['notification']->push($ticket->getMessage(), 'horde.error');
            }
            Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true)
                ->redirect();
        }

        return $ticket;
    }

    /**
     * Get the tabs for navigating between ticket actions.
     */
    function getTicketTabs(&$vars, $id)
    {
        $tabs = new Horde_Core_Ui_Tabs(null, $vars);
        $queue = $vars->get('queue');

        $tabs->addTab(_("_History"), Whups::urlFor('ticket', $id), 'history');
        if (Whups::hasPermission($queue, 'queue', 'update')) {
            $tabs->addTab(_("_Update"),
                          Whups::urlFor('ticket_action', array('update', $id)),
                          'update');
        } else {
            $tabs->addTab(_("_Comment"),
                          Whups::urlFor('ticket_action', array('comment', $id)),
                          'comment');
        }
        $tabs->addTab(_("_Watch"),
                      Whups::urlFor('ticket_action', array('watch', $id)),
                      'watch');
        if (Whups::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $tabs->addTab(_("S_et Queue"),
                          Whups::urlFor('ticket_action', array('queue', $id)),
                          'queue');
        }
        if (Whups::hasPermission($queue, 'queue', 'update')) {
            $tabs->addTab(_("Set _Type"),
                          Whups::urlFor('ticket_action', array('type', $id)),
                          'type');
        }
        if (Whups::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $tabs->addTab(_("_Delete"),
                          Whups::urlFor('ticket_action', array('delete', $id)),
                          'delete');
        }

        return $tabs;
    }

    /**
     * Returns whether a user has a certain permission on a single resource.
     *
     * @param mixed $in                   A single resource to check.
     * @param string $filter              The kind of resource specified in
     *                                    $in, currently only 'queue'.
     * @param string|integer $permission  A permission, either 'assign' or
     *                                    'update', or one of the PERM_*
     *                                    constants.
     * @param string $user                A user name.
     *
     * @return boolean  True if the user has the specified permission.
     */
    function hasPermission($in, $filter, $permission, $user = null)
    {
        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if ($permission == 'update' ||
            $permission == 'assign' ||
            $permission == 'requester') {
            $admin_perm = Horde_Perms::EDIT;
        } else {
            $admin_perm = Horde_Perms::EDIT;
        }

        $admin = $GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => $admin_perm, 'user' => $user));
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        switch ($filter) {
        case 'queue':
            if ($admin) {
                return true;
            }
            switch ($permission) {
            case Horde_Perms::SHOW:
            case Horde_Perms::READ:
            case Horde_Perms::EDIT:
            case Horde_Perms::DELETE:
                if ($perms->hasPermission('whups:queues:' . $in, $user,
                                          $permission)) {
                    return true;
                }
                break;

            default:
                if ($perms->exists('whups:queues:' . $in . ':' . $permission)) {
                    if (($permission == 'update' ||
                         $permission == 'assign' ||
                         $permission == 'requester') &&
                        $perms->getPermissions(
                            'whups:queues:' . $in . ':' . $permission, $user)) {
                        return true;
                    }
                } else {
                    // If the sub-permission doesn't exist, use the queue
                    // permission at an EDIT level and lock out guests.
                    if ($permission != 'requester' &&
                        $GLOBALS['registry']->getAuth() &&
                        $perms->hasPermission('whups:queues:' . $in, $user,
                                              Horde_Perms::EDIT)) {
                        return true;
                    }
                }
                break;
            }
            break;
        }

        return false;
    }

    /**
     * Filters a list of resources based on whether a user use certain
     * permissions on it.
     *
     * @param array $in            A list of resources to check.
     * @param string $filter       The kind of resource specified in $in,
     *                             one of 'queue', 'queue_id', 'reply', or
     *                             'comment'.
     * @param integer $permission  A permission, one of the PERM_* constants.
     * @param string $user         A user name.
     * @param string $creator      The creator of an object in the resource,
     *                             e.g. a ticket creator.
     *
     * @return array  The list of resources matching the permission criteria.
     */
    function permissionsFilter($in, $filter, $permission = Horde_Perms::READ,
                               $user = null, $creator = null)
    {
        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $admin = $GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => $permission, 'user' => $user));
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $out = array();

        switch ($filter) {
        case 'queue':
            if ($admin) {
                return $in;
            }
            foreach ($in as $queueID => $name) {
                if ($perms->hasPermission('whups:queues:' . $queueID, $user,
                                          $permission, $creator)) {
                    $out[$queueID] = $name;
                }
            }
            break;

        case 'queue_id':
            if ($admin) {
                return $in;
            }
            foreach ($in as $queueID) {
                if ($perms->hasPermission('whups:queues:' . $queueID, $user,
                                          $permission, $creator)) {
                    $out[] = $queueID;
                }
            }
            break;

        case 'reply':
            if ($admin) {
                return $in;
            }
            foreach ($in as $replyID => $name) {
                if (!$perms->exists('whups:replies:' . $replyID) ||
                    $perms->hasPermission('whups:replies:' . $replyID,
                                          $user, $permission, $creator)) {
                    $out[$replyID] = $name;
                }
            }
            break;

        case 'comment':
            foreach ($in as $key => $row) {
                foreach ($row as $rkey => $rval) {
                    if ($rkey != 'changes') {
                        $out[$key][$rkey] = $rval;
                        continue;
                    }
                    foreach ($rval as $i => $change) {
                        if ($change['type'] != 'comment' ||
                            !$perms->exists('whups:comments:' . $change['value'])) {
                            $out[$key][$rkey][$i] = $change;
                            if (isset($change['comment'])) {
                                $out[$key]['comment_text'] = $change['comment'];
                            }
                        } elseif ($perms->exists('whups:comments:' . $change['value'])) {
                            $change['private'] = true;
                            $out[$key][$rkey][$i] = $change;
                            if (isset($change['comment'])) {
                                if ($admin ||
                                    $perms->hasPermission('whups:comments:' . $change['value'],
                                                          $user, Horde_Perms::READ, $creator)) {
                                    $out[$key]['comment_text'] = $change['comment'];
                                } else {
                                    $out[$key][$rkey][$i]['comment'] = _("[Hidden]");
                                }
                            }
                        }
                    }
                }
            }
            break;

        default:
            $out = $in;
            break;
        }

        return $out;
    }

    function getOwnerCriteria($user)
    {
        $criteria = array('user:' . $user);
        $groups = Horde_Group::singleton();
        $mygroups = $groups->getGroupMemberships($GLOBALS['registry']->getAuth());
        foreach ($mygroups as $id => $group) {
            $criteria[] = 'group:' . $id;
        }

        return $criteria;
    }

    /**
     */
    function getUserAttributes($user = null)
    {
        static $results;

        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        } elseif (empty($user)) {
            return array('user' => '',
                         'name' => '',
                         'email' => '');
        }

        if (!isset($results[$user])) {
            if (strpos($user, ':') !== false) {
                list($type, $user) = explode(':', $user, 2);
            } else {
                $type = 'user';
            }

            // Default this; some of the cases below might change it.
            $results[$user]['user'] = $user;
            $results[$user]['type'] = $type;

            if ($type == 'user') {
                if (substr($user, 0, 2) == '**') {
                    unset($results[$user]);
                    $user = substr($user, 2);

                    $results[$user]['user'] = $user;
                    $results[$user]['name'] = '';
                    $results[$user]['email'] = '';

                    try {
                        $addr_arr = Horde_Mime_Address::parseAddressList($user);
                        if (isset($addr_arr[0])) {
                            $results[$user]['name'] = isset($addr_arr[0]['personal'])
                                ? $addr_arr[0]['personal'] : '';
                            $results[$user]['email'] = $addr_arr[0]['mailbox'] . '@'
                                . $addr_arr[0]['host'];
                        }
                    } catch (Horde_Mime_Exception $e) {}
                } elseif ($user < 0) {
                    global $whups_driver;

                    $results[$user]['user'] = '';
                    $results[$user]['name'] = '';
                    $results[$user]['email'] = $whups_driver->getGuestEmail($user);

                    try {
                        $addr_arr = Horde_Mime_Address::parseAddressList($results[$user]['email']);
                        if (isset($addr_arr[0])) {
                            $results[$user]['name'] = isset($addr_arr[0]['personal'])
                                ? $addr_arr[0]['personal'] : '';
                            $results[$user]['email'] = $addr_arr[0]['mailbox'] . '@'
                                . $addr_arr[0]['host'];
                        }
                    } catch (Horde_Mime_Exception $e) {}
                } else {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($user);

                    $results[$user]['name'] = $identity->getValue('fullname');
                    $results[$user]['email'] = $identity->getValue('from_addr');
                }
            } elseif ($type == 'group') {
                try {
                    $groups = Horde_Group::singleton();
                    $group = $groups->getGroupById($user);

                    $results[$user]['user'] = $group->getShortName();
                    $results[$user]['name'] = $group->getShortName();
                    $results[$user]['email'] = $group->get('email');
                } catch (Horde_Group_Exception $e) {
                    $results['user']['name'] = '';
                    $results['user']['email'] = '';
                }
            }
        }

        return $results[$user];
    }

    /**
     * Returns a user string from the user's name and email address.
     *
     * @param string|array $user  A user name or a hash as returned from
     *                            {@link Whups::getUserAttributes()}.
     * @param boolean $showemail  Whether to include the email address.
     * @param boolean $showname   Whether to include the full name.
     * @param boolean $html       Whether to "prettify" the result. If true,
     *                            email addresses are obscured, the result is
     *                            escaped for HTML output, and a group icon
     *                            might be added.
     */
    function formatUser($user = null, $showemail = true, $showname = true,
                        $html = false)
    {
        if (!is_null($user) && empty($user)) {
            return '';
        }

        if (is_array($user)) {
            $details = $user;
        } else {
            $details = Whups::getUserAttributes($user);
        }
        if (!empty($details['name'])) {
            $name = $details['name'];
        } else {
            $name = $details['user'];
        }
        if (($showemail || empty($name) || !$showname) &&
            !empty($details['email'])) {
            if ($html && strpos($details['email'], '@') !== false) {
                $details['email'] = str_replace(array('@', '.'),
                                                array(' (at) ', ' (dot) '),
                                                $details['email']);
            }

            if (!empty($name) && $showname) {
                $name .= ' <' . $details['email'] . '>';
            } else {
                $name = $details['email'];
            }
        }

        if ($html) {
            $name = htmlspecialchars($name);
            if ($details['type'] == 'group') {
                $name = Horde::img('group.png',
                                   !empty($details['name'])
                                   ? $details['name']
                                   : $details['user'])
                    . $name;
            }
        }

        return $name;
    }

    /**
     * Returns the set of columns and their associated parameter from the
     * backend that should be displayed to the user. The results can depend on
     * the current user preferences and which search function was executed.
     *
     * @param integer $search_type  The type of search that was executed. Must
     *                              be one of the WHUPS_SEARCH_ constants
     *                              defined above.
     */
    function getSearchResultColumns($search_type = null)
    {
        if ($search_type == 'block') {
            return array(
                _("Id")       => 'id',
                _("Summary")  => 'summary',
                _("Priority") => 'priority_name',
                _("State")    => 'state_name');
        }

        return array(
            _("Id")        => 'id',
            _("Summary")   => 'summary',
            _("State")     => 'state_name',
            _("Type")      => 'type_name',
            _("Priority")  => 'priority_name',
            _("Queue")     => 'queue_name',
            _("Requester") => 'user_id_requester',
            _("Owners")    => 'owners',
            _("Created")   => 'timestamp',
            _("Updated")   => 'date_updated',
            _("Assigned")  => 'date_assigned',
            _("Resolved")  => 'date_resolved',
            );
    }

    /**
     * Send reminders. One email per user.
     *
     * @param Horde_Variables &$vars  The selection criteria.
     */
    function sendReminders(&$vars)
    {
        global $whups_driver;

        // Fetch all unresolved tickets.
        if ($vars->get('id')) {
            $info = array('id' => $vars->get('id'));
        } elseif ($vars->get('queue')) {
            $info['queue'] = $vars->get('queue');
            if ($vars->get('category')) {
                $info['category'] = $vars->get('category');
            } else {
                // Make sure that resolved tickets aren't returned.
                $info['category'] = array('unconfirmed', 'new', 'assigned');
            }
        } else {
            return PEAR::raiseError(_("You must select at least one queue to send reminders for."));
        }

        $tickets = $whups_driver->getTicketsByProperties($info);
        Whups::sortTickets($tickets);
        if (!count($tickets)) {
            return PEAR::raiseError(_("No tickets matched your search criteria."));
        }

        $unassigned = $vars->get('unassigned');
        $remind = array();
        foreach ($tickets as $info) {
            $info['link'] = Whups::urlFor('ticket', $info['id'], true, -1);
            $owners = $whups_driver->getOwners($info['id']);
            if (count($owners)) {
                foreach ($owners as $owner) {
                    $remind[$owner][] = $info;
                }
            } elseif (!empty($unassigned)) {
                $remind['**' . $unassigned][] = $info;
            }
        }

        foreach ($remind as $user => $utickets) {
            if (empty($user) || !count($utickets)) {
                continue;
            }
            $email = "\nHere is a summary of your open tickets:\n";
            foreach ($utickets as $info) {
                if (!empty($email)) {
                    $email .= "\n";
                }
                $email .= "------\n"
                    . 'Ticket #' . $info['id'] . ': ' . $info['summary'] . "\n"
                    . 'Opened: ' . strftime('%a %d %B', $info['timestamp'])
                    . Horde_Form_Type_date::getAgo($info['timestamp']) . "\n"
                    . 'State: ' . $info['state_name'] . "\n"
                    . 'Link: ' . $info['link'] . "\n";
            }
            $email .= "\n";
            $subject = 'Reminder: Your open tickets';
            $whups_driver->mail(null, $user, $subject, $email, $user, true);
        }
    }

    /**
     * Build Whups' list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        $menu = new Horde_Menu();

        if ($GLOBALS['registry']->getAuth()) {
            $menu->add(Horde::applicationUrl('mybugs.php'), sprintf(_("_My %s"), $GLOBALS['registry']->get('name')), 'whups.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'mybugs' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        }
        $menu->add(Horde::applicationUrl('search.php'), _("_Search"), 'search.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'search' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        $menu->add(Horde::applicationUrl('ticket/create.php'), _("_New Ticket"), 'create.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'ticket/create' && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::applicationUrl('query/index.php'), _("_Query Builder"), 'query.png');
        $menu->add(Horde::applicationUrl('reports.php'), _("_Reports"), 'reports.png');

        /* Administration. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin'))) {
            $menu->add(Horde::applicationUrl('admin/'), _("_Admin"), 'admin.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     */
    function getAttachments($ticket, $name = null)
    {
        if (empty($GLOBALS['conf']['vfs']['type'])) {
            return false;
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($vfs->getMessage());
        }

        if ($vfs->isFolder(WHUPS_VFS_ATTACH_PATH, $ticket)) {
            try {
                $files = $vfs->listFolder(WHUPS_VFS_ATTACH_PATH . '/' . $ticket);
            } catch (VFS_Exception $e) {
                $files = array();
            }
            if (is_null($name)) {
                return $files;
            } else {
                foreach ($files as $file) {
                    if ($file['name'] == $name) {
                        return $file;
                    }
                }
            }
        }

        return false;
    }

    /**
     */
    function attachmentUrl($ticket, $file, $queue)
    {
        $link = '';

        // Can we view the attachment online?
        $mime_part = new Horde_Mime_Part();
        $mime_part->setType(Horde_Mime_Magic::extToMime($file['type']));
        $viewer = $GLOBALS['injector']->getInstance('Horde_Mime_Viewer')->getViewer($mime_part);
        if ($viewer && !($viewer instanceof Horde_Mime_Viewer_Default)) {
            $url = Horde_Util::addParameter(Horde::applicationUrl('view.php'),
                                      array('actionID' => 'view_file',
                                            'type' => $file['type'],
                                            'file' => $file['name'],
                                            'ticket' => $ticket));
            $link .= Horde::link($url, $file['name'], null, '_blank') . $file['name'] . '</a>';
        } else {
            $link .= $file['name'];
        }

        // We can always download attachments.
        $url_params = array('actionID' => 'download_file',
                            'file' => $file['name'],
                            'ticket' => $ticket);
        $link .= ' ' . Horde::link(Horde::downloadUrl($file['name'], $url_params), $file['name']) . Horde::img('download.png', _("Download")) . '</a>';

        // Admins can delete attachments.
        if (Whups::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $url = Horde_Util::addParameter(
                Horde::applicationUrl('ticket/delete_attachment.php'),
                array('file' => $file['name'],
                      'id' => $ticket,
                      'url' => Horde::selfUrl(true, false, true)));
            $link .= ' ' . Horde::link($url, sprintf(_("Delete %s"), $file['name']), '', '', 'return window.confirm(\'' . addslashes(sprintf(_("Permanently delete %s?"), $file['name'])) . '\');') .
                Horde::img('delete.png', sprintf(_("Delete %s"), $file['name'])) . '</a>';
        }

        return $link;
    }

    function getOwners($ticket, $showemail = true, $showname = true, $owners = null)
    {
        if (is_null($owners)) {
            global $whups_driver;
            $owners = $whups_driver->getOwners($ticket);
            if (is_a($owners, 'PEAR_Error')) {
                Horde::logMessage($owners, 'ERR');
                return $owners->getMessage();
            }
        }

        $results = array();
        foreach ($owners as $owner) {
            $results[] = Whups::formatUser($owner, $showemail, $showname);
        }

        return implode(', ', $results);
    }

    /**
     * Add inline javascript to the output buffer.
     *
     * @param mixed $script  The script text to add (can be stored in an
     *                       array also).
     *
     * @return string  The javascript text to output, or empty if the page
     *                 headers have not yet been sent.
     */
    function addInlineScript($script)
    {
        if (is_array($script)) {
            $script = implode(';', $script);
        }

        $script = trim($script);
        if (empty($script)) {
            return;
        }

        if (!isset($GLOBALS['__whups_inline_script'])) {
            $GLOBALS['__whups_inline_script'] = array();
        }
        $GLOBALS['__whups_inline_script'][] = $script;

        // If headers have already been sent, we need to output a
        // <script> tag directly.
        if (ob_get_length() || headers_sent()) {
            Whups::outputInlineScript();
        }
    }

    /**
     * Print inline javascript to the output buffer.
     *
     * @return string  The javascript text to output.
     */
    function outputInlineScript()
    {
        if (!empty($GLOBALS['__whups_inline_script'])) {
            echo '<script type="text/javascript">//<![CDATA[' . "\n";
            foreach ($GLOBALS['__whups_inline_script'] as $val) {
                echo $val . "\n";
            }
            echo "//]]></script>\n";
        }

        $GLOBALS['__whups_inline_script'] = array();
    }

    /**
     * Retruns the available field types including all type information from
     * the Horde_Form classes.
     *
     * @return array  The full field types array.
     */
    function fieldTypes()
    {
        static $fields_array = array();
        if (!empty($fields_array)) {
            return $fields_array;
        }

        /* Fetch all declared classes. */
        require_once 'Horde/Form.php';
        $classes = get_declared_classes();

        /* Filter for the Horde_Form_Type classes. */
        foreach ($classes as $class) {
            if (strtolower(substr($class, 0, 16)) == 'horde_form_type_') {
                $field_type = substr($class, 16);
                /* Don't bother including the types that cannot be handled
                 * usefully. */
                $blacklist = array('invalid', 'addresslink', 'spacer',
                                   'description', 'captcha', 'figlet',
                                   'header');
                if (in_array($field_type, $blacklist)) {
                    continue;
                }
                $fields_array[$field_type] = @call_user_func(
                    array('Horde_Form_Type_' . $field_type, 'about'));
            }
        }

        return $fields_array;
    }

    /**
     * Returns the available field type names from the Horde_Form classes.
     *
     * @return array  A hash The with available field types and names.
     */
    function fieldTypeNames()
    {
        /* Fetch the field type information from the Horde_Form classes. */
        $fields = Whups::fieldTypes();

        /* Strip out the name element from the array. */
        $available_fields = array();
        foreach ($fields as $field_type => $info) {
            $available_fields[$field_type] = $info['name'];
        }

        /* Sort for display purposes. */
        asort($available_fields);

        return $available_fields;
    }

    /**
     * Returns the parameters for a certain Horde_Form field type.
     *
     * @param string $field_type  A field type.
     *
     * @return array  A list of field type parameters.
     */
    function fieldTypeParams($field_type)
    {
        $fields = Whups::fieldTypes();

        return isset($fields[$field_type]['params'])
            ? $fields[$field_type]['params']
            : array();
    }

    /**
     * Determines parameters needed to do an address search
     *
     * @return array  An array with two keys: 'sources' and 'fields'.
     */
    static public function getAddressbookSearchParams()
    {
        $src = json_decode($GLOBALS['prefs']->getValue('search_sources'));
        if (!is_array($src)) {
            $src = array();
        }

        $fields = json_decode($GLOBALS['prefs']->getValue('search_fields'), true);
        if (!is_array($fields)) {
            $fields = array();
        }

        return array(
            'fields' => $fields,
            'sources' => $src
        );
    }

}
