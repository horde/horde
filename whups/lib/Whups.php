<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

/**
 * The Whups:: class provides functionality that all of Whups needs,
 * or that should be encapsulated from other parts of the Whups
 * system.
 *
 * @package Whups
 */
class Whups
{
    const VFS_ATTACH_PATH = '.horde/whups/attachments';

    static public function urlFor($controller, $data, $full = false, $append_session = 0)
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
                return Horde::url('queue/' . $slug, $full, $append_session);
            } else {
                if (is_array($data)) {
                    $id = $data['id'];
                } else {
                    $id = $data;
                }
                return Horde::url('queue/?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket':
            $id = (int)$data;
            if ($rewrite) {
                return Horde::url('ticket/' . $id, $full, $append_session);
            } else {
                return Horde::url('ticket/?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket_rss':
            $id = (int)$data;
            if ($rewrite) {
                return Horde::url('ticket/' . $id . '/rss', $full, $append_session);
            } else {
                return Horde::url('ticket/rss.php?id=' . $id, $full, $append_session);
            }
            break;

        case 'ticket_action':
            list($controller, $id) = $data;
            if ($rewrite) {
                return Horde::url('ticket/' . $id . '/' . $controller, $full, $append_session = 0);
            } else {
                return Horde::url('ticket/' . $controller . '.php?id=' . $id, $full, $append_session = 0);
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
                return Horde::url($url, $full, $append_session);
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
                return Horde::url($url, $full, $append_session);
            }
            break;
        }
    }

    /**
     * Sort tickets by requested direction and fields
     *
     * @param array $tickets  The array of tickets to sort
     * @param string $by      The field to sort by. If omitted, obtain from
     *                        prefs
     * @param string $dir     The direction to sort. If omitted, obtain from
     *                        prefs
     *
     * @return array  The sorted array of tickets.
     */
    static public function sortTickets(&$tickets, $by = null, $dir = null)
    {
        if (is_null($by)) {
            $by = $GLOBALS['prefs']->getValue('sortby');
        }
        if (is_null($dir)) {
            $dir = $GLOBALS['prefs']->getValue('sortdir');
        }

        self::sortBy($by);
        self::sortDir($dir);

        // Do some prep for sorting.
        $tickets = array_map(array('Whups', '_prepareSort'), $tickets);

        usort($tickets, array('Whups', '_sort'));
    }

    /**
     * Set or obtain the current sortBy value.
     *
     * @param string $b  The field to sort by.
     *
     * @return  If $b is null, returns the previously set value, null otherwise.
     */
    static public function sortBy($b = null)
    {
        static $by;

        if (!is_null($b)) {
            $by = $b;
        } else {
            return $by;
        }
    }

    /**
     * Set or obtain the current sortdir value.
     *
     * @param string $d  The direction to sort by.
     *
     * @return  If $d is null, returns the previously set value, null otherwise.
     */
    static public function sortDir($d = null)
    {
        static $dir;

        if (!is_null($d)) {
            $dir = $d;
        } else {
            return $dir;
        }
    }

    /**
     * Helper method to prepare an array of tickets for sorting. Adds a sort_by
     * key to each ticket array, with values lowercased. Used a new key in order
     * to avoid altering the raw value. Used as a callback to array_map()
     *
     * @param array $ticket  The ticket array to prepare.
     *
     * @return array  The altered $ticket array
     */
    static protected function _prepareSort(array $ticket) {
        $by = self::sortBy();
        $ticket['sort_by'] = array();
        if (is_array($by)) {
            foreach ($by as $field) {
                $ticket['sort_by'][$field] = Horde_String::lower($ticket[$field], true, 'UTF-8');
            }
        } else {
            if (is_array($ticket[$by])) {
                natcasesort($ticket[$by]);
                $ticket['sort_by'][$by] = implode('', $ticket[$by]);
            } else {
                $ticket['sort_by'][$by] = Horde_String::lower($ticket[$by], true, 'UTF-8');
            }
        }
        return $ticket;
    }

    /**
     * Helper method to sort an array of tickets. Used as callback to usort().
     *
     * @param array $a         The first ticket to compare
     * @param array $b         The secon ticket to compare
     * @param string $sortby   The field to sortby. If null, uses the field from
     *                         self::sortBy()
     * @param string $sortdir  The direction to sort. If null, uses the value
     *                         from self::sortDir().
     *
     * @return integer
     */
    static protected function _sort($a, $b, $sortby = null, $sortdir = null)
    {
        static $by, $dir;
        if (is_null($by)) {
            $by = self::sortBy();
            $dir = self::sortDir();
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
            } elseif ($a['sort_by'][$sortby[0]] > $b['sort_by'][$sortby[0]]) {
                return $sortdir[0] ? -1 : 1;
            } elseif ($a['sort_by'][$sortby[0]] === $b['sort_by'][$sortby[0]]) {
                array_shift($sortby);
                array_shift($sortdir);
                return self::_sort($a, $b, $sortby, $sortdir);
            } else {
                return $sortdir[0] ? 1 : -1;
            }
        } else {
            $a_val = isset($a['sort_by'][$sortby]) ? $a['sort_by'][$sortby] : null;
            $b_val = isset($b['sort_by'][$sortby]) ? $b['sort_by'][$sortby] : null;

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
                // Some special case sorting
                if (is_array($a_val) || is_array($b_val)) {
                    $a_val = implode('', $a_val);
                    $b_val = implode('', $b_val);
                }

                // String comparison
                if ($sortdir) {
                    return strcoll($b_val, $a_val);
                } else {
                    return strcoll($a_val, $b_val);
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
    static public function getCAPTCHA($new = false)
    {
        global $session;

        if ($new || !$session->get('whups', 'captcha')) {
            $captcha = '';
            for ($i = 0; $i < 5; ++$i) {
                $captcha .= chr(rand(65, 90));
            }
            $session->set('whups', 'captcha', $captcha);
        }

        return $session->get('whups', 'captcha');
    }

    /**
     * List all templates of a given type.
     *
     * @param string $type  The kind of template ('searchresults', etc.) to list.
     *
     * @return array  All templates of the requested type.
     */
    static public function listTemplates($type)
    {
        $templates = array();

        $_templates = Horde::loadConfiguration('templates.php', '_templates', 'whups');
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
   static public  function getCurrentTicket()
    {
        $id = preg_replace('|\D|', '', Horde_Util::getFormData('id'));
        if (!$id) {
            $GLOBALS['notification']->push(_("Invalid Ticket Id"), 'horde.error');
            Horde::url($GLOBALS['prefs']->getValue('whups_default_view') . '.php', true)
                ->redirect();
        }

        try {
            return Whups_Ticket::makeTicket($id);
        } catch (Whups_Exception $e) {
            if ($ticket->code === 0) {
                // No permissions to this ticket.
                $GLOBALS['notification']->push($e->getMessage(), 'horde.warning');
            } else {
                $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
            }
            Horde::url($GLOBALS['prefs']->getValue('whups_default_view') . '.php', true)
                ->redirect();
        }
    }

    /**
     * Get the tabs for navigating between ticket actions.
     */
   static public  function getTicketTabs(&$vars, $id)
    {
        $tabs = new Horde_Core_Ui_Tabs(null, $vars);
        $queue = $vars->get('queue');

        $tabs->addTab(_("_History"), self::urlFor('ticket', $id), 'history');
        if (self::hasPermission($queue, 'queue', 'update')) {
            $tabs->addTab(_("_Update"),
                          self::urlFor('ticket_action', array('update', $id)),
                          'update');
        } else {
            $tabs->addTab(_("_Comment"),
                          self::urlFor('ticket_action', array('comment', $id)),
                          'comment');
        }
        $tabs->addTab(_("_Watch"),
                      self::urlFor('ticket_action', array('watch', $id)),
                      'watch');
        if (self::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $tabs->addTab(_("S_et Queue"),
                          self::urlFor('ticket_action', array('queue', $id)),
                          'queue');
        }
        if (self::hasPermission($queue, 'queue', 'update')) {
            $tabs->addTab(_("Set _Type"),
                          self::urlFor('ticket_action', array('type', $id)),
                          'type');
        }
        if (self::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $tabs->addTab(_("_Delete"),
                          self::urlFor('ticket_action', array('delete', $id)),
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
    static public function hasPermission($in, $filter, $permission, $user = null)
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
   static public  function permissionsFilter($in, $filter, $permission = Horde_Perms::READ,
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
        $mygroups = $GLOBALS['injector']
            ->getInstance('Horde_Group')
            ->listGroups($GLOBALS['registry']->getAuth());
        foreach ($mygroups as $id => $group) {
            $criteria[] = 'group:' . $id;
        }

        return $criteria;
    }

    /**
     */
    static public function getUserAttributes($user = null)
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
                    $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);

                    $results[$user]['name'] = $identity->getValue('fullname');
                    $results[$user]['email'] = $identity->getValue('from_addr');
                }
            } elseif ($type == 'group') {
                try {
                    $group = $GLOBALS['injector']
                        ->getInstance('Horde_Group')
                        ->getData($user);
                    $results[$user]['user'] = $group['name'];
                    $results[$user]['name'] = $group['name'];
                    $results[$user]['email'] = $group['email'];
                } catch (Horde_Exception $e) {
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
     *                            {@link self::getUserAttributes()}.
     * @param boolean $showemail  Whether to include the email address.
     * @param boolean $showname   Whether to include the full name.
     * @param boolean $html       Whether to "prettify" the result. If true,
     *                            email addresses are obscured, the result is
     *                            escaped for HTML output, and a group icon
     *                            might be added.
     */
    static public function formatUser($user = null, $showemail = true, $showname = true,
                        $html = false)
    {
        if (!is_null($user) && empty($user)) {
            return '';
        }

        if (is_array($user)) {
            $details = $user;
        } else {
            $details = self::getUserAttributes($user);
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
    static public function getSearchResultColumns($search_type = null)
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
    static public  function sendReminders(&$vars)
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
        self::sortTickets($tickets);
        if (!count($tickets)) {
            return PEAR::raiseError(_("No tickets matched your search criteria."));
        }

        $unassigned = $vars->get('unassigned');
        $remind = array();
        foreach ($tickets as $info) {
            $info['link'] = self::urlFor('ticket', $info['id'], true, -1);
            $owners = current($whups_driver->getOwners($info['id']));
            if (count($owners)) {
                foreach ($owners as $owner) {
                    $remind[$owner] = $info;
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
    static public function getMenu($returnType = 'object')
    {
        $menu = new Horde_Menu();
        $menu->add(Horde::url('mybugs.php'), sprintf(_("_My %s"), $GLOBALS['registry']->get('name')), 'whups.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'mybugs' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'search' && strpos($_SERVER['PHP_SELF'], $GLOBALS['registry']->get('webroot') . '/index.php') !== false ? 'current' : null);
        $menu->add(Horde::url('ticket/create.php'), _("_New Ticket"), 'create.png', null, null, null, $GLOBALS['prefs']->getValue('whups_default_view') == 'ticket/create' && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::url('query/index.php'), _("_Query Builder"), 'query.png');
        $menu->add(Horde::url('reports.php'), _("_Reports"), 'reports.png');

        /* Administration. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin'))) {
            $menu->add(Horde::url('admin/'), _("_Admin"), 'admin.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     */
    static public function getAttachments($ticket, $name = null)
    {
        if (empty($GLOBALS['conf']['vfs']['type'])) {
            return false;
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            return PEAR::raiseError($vfs->getMessage());
        }

        if ($vfs->isFolder(self::VFS_ATTACH_PATH, $ticket)) {
            try {
                $files = $vfs->listFolder(self::VFS_ATTACH_PATH . '/' . $ticket);
            } catch (Horde_Vfs_Exception $e) {
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
    static public function attachmentUrl($ticket, $file, $queue)
    {
        $link = '';

        // Can we view the attachment online?
        $mime_part = new Horde_Mime_Part();
        $mime_part->setType(Horde_Mime_Magic::extToMime($file['type']));
        $viewer = $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part);
        if ($viewer && !($viewer instanceof Horde_Mime_Viewer_Default)) {
            $url = Horde_Util::addParameter(Horde::url('view.php'),
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
        if (self::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $url = Horde_Util::addParameter(
                Horde::url('ticket/delete_attachment.php'),
                array('file' => $file['name'],
                      'id' => $ticket,
                      'url' => Horde::selfUrl(true, false, true)));
            $link .= ' ' . Horde::link($url, sprintf(_("Delete %s"), $file['name']), '', '', 'return window.confirm(\'' . addslashes(sprintf(_("Permanently delete %s?"), $file['name'])) . '\');') .
                Horde::img('delete.png', sprintf(_("Delete %s"), $file['name'])) . '</a>';
        }

        return $link;
    }

    /**
     * Obtain formatted owner string
     *
     * @param integer $ticket    The ticket id. Only used if $owners is null.
     * @param boolean $showmail  Should we include the email address in the
     *                           output?
     * @param boolean $showname  Should we include the name in the output?
     * @param array $owners      An array of owners as returned from
     *                           Whups_Driver::getOwners() to be formatted. If
     *                           this is provided, they are used in place of
     *                           fetcing owners from $ticket.
     *
     * @return string  The formatted owner string.
     */
    static public function getOwners(
        $ticket, $showemail = true, $showname = true, $owners = null)
    {
        if (is_null($owners)) {
            global $whups_driver;
            $owners = $whups_driver->getOwners($ticket);
        }

        $results = array();
        $owners = current($owners);
        foreach ($owners as $owner) {
            $results[] = self::formatUser($owner, $showemail, $showname);
        }

        return implode(', ', $results);
    }

    /**
     * Retruns the available field types including all type information from
     * the Horde_Form classes.
     *
     * @return array  The full field types array.
     */
   static public  function fieldTypes()
    {
        static $fields_array = array();
        if (!empty($fields_array)) {
            return $fields_array;
        }

        /* Fetch all declared classes. */
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
    static public function fieldTypeNames()
    {
        /* Fetch the field type information from the Horde_Form classes. */
        $fields = self::fieldTypes();

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
    static public function fieldTypeParams($field_type)
    {
        $fields = self::fieldTypes();

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
