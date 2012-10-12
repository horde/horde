<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

/**
 * The Whups class provides functionality that all of Whups needs, or that
 * should be encapsulated from other parts of the Whups system.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */
class Whups
{
    /**
     * Path to ticket attachments in the VFS.
     */
    const VFS_ATTACH_PATH = '.horde/whups/attachments';

    /**
     * The current sort field.
     *
     * @see sortBy()
     * @var string
     */
    static protected $_sortBy;

    /**
     * The current sort direction.
     *
     * @see sortDir()
     * @var integer
     */
    static protected $_sortDir;

    /**
     * Cached list of user information.
     *
     * @see getUserAttributes()
     * @var array
     */
    static protected $_users = array();

    /**
     * All available form field types including all type information
     * from the Horde_Form classes.
     *
     * @see fieldTypes()
     * @var array
     */
    static protected $_fieldTypes = array();

    /**
     * URL factory.
     *
     * @param string $controller       The controller to link to, one of
     *                                 'queue', 'ticket', 'ticket_rss',
     *                                 'ticket_action', 'query', 'query_rss'.
     * @param array|string $data       URL data, depending on the controller.
     * @param boolean $full            @see Horde::url()
     * @param integer $append_session  @see Horde::url()
     *
     * @return Horde_Url  The generated URL.
     */
    static public function urlFor($controller, $data, $full = false,
                                  $append_session = 0)
    {
        $rewrite = isset($GLOBALS['conf']['urls']['pretty']) &&
            $GLOBALS['conf']['urls']['pretty'] == 'rewrite';

        switch ($controller) {
        case 'queue':
            if ($rewrite) {
                if (is_array($data)) {
                    if (empty($data['slug'])) {
                        $slug = (int)$data['id'];
                    } else {
                        $slug = $data['slug'];
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
                return Horde::url($url, $full, $append_session)->add($param);
            }
            break;
        }
    }

    /**
     * Sorts tickets by requested direction and fields.
     *
     * @param array $tickets  The list of tickets to sort.
     * @param string $by      The field to sort by. If omitted, obtain from
     *                        preferences.
     * @param string $dir     The direction to sort. If omitted, obtain from
     *                        preferences.
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
     * Sets or returns the current sort field.
     *
     * @param string $b  The field to sort by.
     *
     * @return string  If $b is null, returns the previously set value.
     */
    static public function sortBy($b = null)
    {
        if (!is_null($b)) {
            self::$_sortBy = $b;
        } else {
            return self::$_sortBy;
        }
    }

    /**
     * Sets or returns the current sort direction.
     *
     * @param integer $d  The direction to sort by.
     *
     * @return integer  If $d is null, returns the previously set value.
     */
    static public function sortDir($d = null)
    {
        if (!is_null($d)) {
            self::$_sortDir = $d;
        } else {
            return self::$_sortDir;
        }
    }

    /**
     * Helper method to prepare an array of tickets for sorting.
     *
     * Adds a sort_by key to each ticket array, with values lowercased. Used as
     * a callback to array_map().
     *
     * @param array $ticket  The ticket array to prepare.
     *
     * @return array  The altered $ticket array
     */
    static protected function _prepareSort(array $ticket)
    {
        $by = self::sortBy();
        $ticket['sort_by'] = array();
        if (is_array($by)) {
            foreach ($by as $field) {
                if (!isset($ticket[$field])) {
                    $ticket['sort_by'][$field] = '';
                } else {
                    $ticket['sort_by'][$field] = Horde_String::lower($ticket[$field], true, 'UTF-8');
                }
            }
        } else {
            if (!isset($ticket[$by])) {
                $ticket['sort_by'][$by] = '';
            } elseif (is_array($ticket[$by])) {
                natcasesort($ticket[$by]);
                $ticket['sort_by'][$by] = implode('', $ticket[$by]);
            } else {
                $ticket['sort_by'][$by] = Horde_String::lower($ticket[$by], true, 'UTF-8');
            }
        }
        return $ticket;
    }

    /**
     * Helper method to sort an array of tickets.
     *
     * Used as callback to usort().
     *
     * @param array $a         The first ticket to compare.
     * @param array $b         The second ticket to compare.
     * @param string $sortby   The field to sort by. If null, uses the field
     *                         from self::sortBy().
     * @param string $sortdir  The direction to sort. If null, uses the value
     *                         from self::sortDir().
     *
     * @return integer
     */
    static protected function _sort($a, $b, $sortby = null, $sortdir = null)
    {
        if (is_null($sortby)) {
            $sortby = self::$_sortBy;
        }
        if (is_null($sortdir)) {
            $sortdir = self::$_sortDir;
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
            }
            if ($a['sort_by'][$sortby[0]] > $b['sort_by'][$sortby[0]]) {
                return $sortdir[0] ? -1 : 1;
            }
            if ($a['sort_by'][$sortby[0]] === $b['sort_by'][$sortby[0]]) {
                array_shift($sortby);
                array_shift($sortdir);
                return self::_sort($a, $b, $sortby, $sortdir);
            }
            return $sortdir[0] ? 1 : -1;
        }

        $a_val = isset($a['sort_by'][$sortby]) ? $a['sort_by'][$sortby] : null;
        $b_val = isset($b['sort_by'][$sortby]) ? $b['sort_by'][$sortby] : null;

        // Take care of the simplest case first
        if ($a_val === $b_val) {
            return 0;
        }

        if ((is_numeric($a_val) || is_null($a_val)) &&
            (is_numeric($b_val) || is_null($b_val))) {
            // Numeric comparison
            return (int)($sortdir ? ($b_val > $a_val) : ($a_val > $b_val));
        }

        // Some special case sorting
        if (is_array($a_val) || is_array($b_val)) {
            $a_val = implode('', $a_val);
            $b_val = implode('', $b_val);
        }

        // String comparison
        return $sortdir ? strcoll($b_val, $a_val) : strcoll($a_val, $b_val);
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
     * Lists all templates of a given type.
     *
     * @param string $type  The kind of template ('searchresults', etc.) to
     *                      list.
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
     * Returns the current ticket.
     *
     * Uses the 'id' request variable to determine what to look for. Will
     * redirect to the default view if the ticket isn't found or if permissions
     * checks fail.
     *
     * @return Whups_Ticket  The current ticket.
     */
    static public function getCurrentTicket()
    {
        $default = Horde::url($GLOBALS['prefs']->getValue('whups_default_view') . '.php', true);

        $id = preg_replace('|\D|', '', Horde_Util::getFormData('id'));
        if (!$id) {
            $GLOBALS['notification']->push(_("Invalid Ticket Id"), 'horde.error');
            $default->redirect();
        }

        try {
            return Whups_Ticket::makeTicket($id);
        } catch (Whups_Exception $e) {
            if ($ticket->code === 0) {
                // No permissions to this ticket.
                $GLOBALS['notification']->push($e->getMessage(), 'horde.warning');
                $default->redirect();
            }
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e);
            $default->redirect();
        }
    }

    /**
     * Returns the tabs for navigating between ticket actions.
     */
    static public function getTicketTabs(&$vars, $id)
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
     *                                    'update', 'requester', or one of the
     *                                    PERM_* constants.
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
            $admin_perm = $permission;
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
     * Filters a list of resources based on whether a user has certain
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
    static public function permissionsFilter($in, $filter,
                                             $permission = Horde_Perms::READ,
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

    /**
     * Builds a list of criteria for Whups_Driver#getTicketsByProperties() that
     * match a certain user.
     *
     * Merges the user's groups with the user name.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of criteria that would match the user.
     */
    static public function getOwnerCriteria($user)
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
     * Returns a hash with user information.
     *
     * @param string $user  A (Whups) user name, defaults to the current user.
     *
     * @return array  An information hash with 'user', 'name', 'email', and
     *                'type' values.
     */
    static public function getUserAttributes($user = null)
    {
        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        } elseif (empty($user)) {
            return array('user' => '',
                         'name' => '',
                         'email' => '');
        }

        if (isset(self::$_users[$user])) {
            return self::$_users[$user];
        }

        if (strpos($user, ':') !== false) {
            list($type, $user) = explode(':', $user, 2);
        } else {
            $type = 'user';
        }

        // Default this; some of the cases below might change it.
        self::$_users[$user]['user'] = $user;
        self::$_users[$user]['type'] = $type;

        switch ($type) {
        case 'user':
            if (substr($user, 0, 2) == '**') {
                unset(self::$_users[$user]);
                $user = substr($user, 2);

                self::$_users[$user]['user'] = $user;
                self::$_users[$user]['name'] = '';
                self::$_users[$user]['email'] = '';

                $addr_ob = new Horde_Mail_Rfc822_Address($user);
                if ($addr_ob->valid) {
                    self::$_users[$user]['name'] = is_null($addr_ob->personal)
                        ? ''
                        : $addr_ob->personal;
                    self::$_users[$user]['email'] = $addr_ob->bare_address;
                }
            } elseif ($user < 0) {
                global $whups_driver;

                self::$_users[$user]['user'] = '';
                self::$_users[$user]['name'] = '';
                self::$_users[$user]['email'] = $whups_driver->getGuestEmail($user);

                $addr_ob = new Horde_Mail_Rfc822_Address(self::$_users[$user]['email']);
                if ($addr_ob->valid) {
                    self::$_users[$user]['name'] = is_null($addr_ob->personal)
                        ? ''
                        : $addr_ob->personal;
                    self::$_users[$user]['email'] = $addr_ob->bare_address;
                }
            } else {
                $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);

                self::$_users[$user]['name'] = $identity->getValue('fullname');
                self::$_users[$user]['email'] = $identity->getValue('from_addr');
            }
            break;

        case 'group':
            try {
                $group = $GLOBALS['injector']
                    ->getInstance('Horde_Group')
                    ->getData($user);
                self::$_users[$user]['user'] = $group['name'];
                self::$_users[$user]['name'] = $group['name'];
                self::$_users[$user]['email'] = $group['email'];
            } catch (Horde_Exception $e) {
                self::$_users['user']['name'] = '';
                self::$_users['user']['email'] = '';
            }
            break;
        }

        return self::$_users[$user];
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
    static public function formatUser($user = null, $showemail = true,
                                      $showname = true, $html = false)
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
     * Formats a ticket property for a tabular ticket listing.
     *
     * @param array $info    A ticket information hash.
     * @param string $value  The column/property to format.
     *
     * @return string  The formatted property.
     */
    static public function formatColumn($info, $value)
    {
        $url = Whups::urlFor('ticket', $info['id']);
        $thevalue = isset($info[$value]) ? $info[$value] : '';

        if ($value == 'timestamp' || $value == 'due' ||
            substr($value, 0, 5) == 'date_') {
            require_once 'Horde/Form/Type.php';
            $thevalue = Horde_Form_Type_date::getFormattedTime(
                $thevalue,
                $GLOBALS['prefs']->getValue('report_time_format'),
                false);
        } elseif ($value == 'user_id_requester') {
            $thevalue = $info['requester_formatted'];
        } elseif ($value == 'id' || $value == 'summary') {
            $thevalue = Horde::link($url) . '<strong>' . htmlspecialchars($thevalue) . '</strong></a>';
        } elseif ($value == 'owners') {
            if (!empty($info['owners_formatted'])) {
                $thevalue = implode(', ', $info['owners_formatted']);
            }
        }

        return $thevalue;
    }

    /**
     * Returns the set of columns and their associated parameter from the
     * backend that should be displayed to the user.
     *
     * The results can depend on the current user preferences, which search
     * function was executed, and the $columns parameter.
     *
     * @param integer $search_type  The type of search that was executed.
     *                              Currently only 'block' is supported.
     * @param array $columns        The columns to return, overriding the
     *                              defaults for some $search_type.
     */
    static public function getSearchResultColumns($search_type = null,
                                                  $columns = null)
    {
        $all = array(
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
            _("Due")       => 'due',
            _("Resolved")  => 'date_resolved',
        );

        if ($search_type != 'block') {
            return $all;
        }

        if (is_null($columns)) {
            $columns = array('summary', 'priority_name', 'state_name');
        }

        $result = array(_("Id") => 'id');
        foreach ($columns as $param) {
            if (($label = array_search($param, $all)) !== false) {
                $result[$label] = $param;
            }
        }

        return $result;
    }

    /**
     * Sends reminders, one email per user.
     *
     * @param Horde_Variables $vars  The selection criteria:
     *                               - 'id' (integer) for individual tickets
     *                               - 'queue' (integer) for tickets of a queue.
     *                                 - 'category' (array) for ticket
     *                                   categories, defaults to unresolved
     *                                   tickets.
     *                               - 'unassigned' (boolean) for unassigned
     *                                 tickets.
     *
     * @throws Whups_Exception
     */
    static public function sendReminders($vars)
    {
        global $whups_driver;

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
            throw new Whups_Exception(_("You must select at least one queue to send reminders for."));
        }

        $tickets = $whups_driver->getTicketsByProperties($info);
        self::sortTickets($tickets);
        if (!count($tickets)) {
            throw new Whups_Exception(_("No tickets matched your search criteria."));
        }

        $unassigned = $vars->get('unassigned');
        $remind = array();
        foreach ($tickets as $info) {
            $info['link'] = self::urlFor('ticket', $info['id'], true, -1);
            $owners = current($whups_driver->getOwners($info['id']));
            if (!empty($owners)) {
                foreach ($owners as $owner) {
                    $remind[$owner][] = $info;
                }
            } elseif (!empty($unassigned)) {
                $remind['**' . $unassigned][] = $info;
            }
        }

        /* Build message template. */
        $view = new Horde_View(array('templatePath' => WHUPS_BASE . '/config'));
        $view->date = strftime($GLOBALS['prefs']->getValue('date_format'));

        /* Get queue specific notification message text, if available. */
        $message_file = WHUPS_BASE . '/config/reminder_email.plain';
        if (file_exists($message_file . '.local.php')) {
            $message_file .= '.local.php';
        } else {
            $message_file .= '.php';
        }
        $message_file = basename($message_file);

        foreach ($remind as $user => $utickets) {
            if (empty($user) || !count($utickets)) {
                continue;
            }
            $view->tickets = $utickets;
            $subject = _("Reminder: Your open tickets");
            $whups_driver->mail(array('recipients' => array($user => 'owner'),
                                      'subject' => $subject,
                                      'view' => $view,
                                      'template' => $message_file,
                                      'from' => $user));
        }
    }

    /**
     * Returns attachment information hashes from the VFS backend.
     *
     * @param integer $ticket  A ticket ID.
     * @param string $name     An attachment name.
     *
     * @return array  If $name is empty a list of all attachments' information
     *                hashes, otherwise only the hash for the attachment of
     *                that name.
     *
     * @throws Whups_Exception if the VFS object cannot be created.
     */
    static public function getAttachments($ticket, $name = null)
    {
        if (empty($GLOBALS['conf']['vfs']['type'])) {
            return;
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            throw new Whups_Exception($e);
        }

        if (!$vfs->isFolder(self::VFS_ATTACH_PATH, $ticket)) {
            return;
        }

        try {
            $files = $vfs->listFolder(self::VFS_ATTACH_PATH . '/' . $ticket);
        } catch (Horde_Vfs_Exception $e) {
            $files = array();
        }
        if (is_null($name)) {
            return $files;
        }
        foreach ($files as $file) {
            if ($file['name'] == $name) {
                return $file;
            }
        }
    }

    /**
     * Returns the links to view, download, and delete an attachment.
     *
     * @param integer $ticket  A ticket ID.
     * @param string $file     An attachment name.
     * @param integer $queue   The ticket's queue ID.
     */
    static public function attachmentUrl($ticket, $file, $queue)
    {
        $link = '';

        // Can we view the attachment online?
        $mime_part = new Horde_Mime_Part();
        $mime_part->setType(Horde_Mime_Magic::extToMime($file['type']));
        $viewer = $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part);
        if ($viewer && !($viewer instanceof Horde_Mime_Viewer_Default)) {
            $url = Horde::url('view.php')->add(array(
                'actionID' => 'view_file',
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
        $link .= ' ' . Horde::link($GLOBALS['registry']->downloadUrl($file['name'], $url_params), $file['name']) . Horde::img('download.png', _("Download")) . '</a>';

        // Admins can delete attachments.
        if (self::hasPermission($queue, 'queue', Horde_Perms::DELETE)) {
            $url = Horde::url('ticket/delete_attachment.php')->add(
                array('file' => $file['name'],
                      'id' => $ticket,
                      'url' => Horde::selfUrl(true, false, true)));
            $link .= ' ' . Horde::link($url, sprintf(_("Delete %s"), $file['name']), '', '', 'return window.confirm(\'' . addslashes(sprintf(_("Permanently delete %s?"), $file['name'])) . '\');') .
                Horde::img('delete.png', sprintf(_("Delete %s"), $file['name'])) . '</a>';
        }

        return $link;
    }

    /**
     * Returns formatted owner names of a ticket.
     *
     * @param integer $ticket    A ticket id. Only used if $owners is null.
     * @param boolean $showmail  Should we include the email address in the
     *                           output?
     * @param boolean $showname  Should we include the name in the output?
     * @param array $owners      An array of owners as returned from
     *                           Whups_Driver::getOwners() to be formatted. If
     *                           this is provided, they are used instead of
     *                           the owners from $ticket.
     *
     * @return string  The formatted owner string.
     */
    static public function getOwners($ticket, $showemail = true,
                                     $showname = true, $owners = null)
    {
        if (is_null($owners)) {
            $owners = $GLOBALS['whups_driver']->getOwners($ticket);
        }

        $results = array();
        $owners = reset($owners);
        if ($owners !== false) {
            foreach ($owners as $owner) {
                $results[] = self::formatUser($owner, $showemail, $showname);
            }
        }
        return implode(', ', $results);
    }

    /**
     * Returns all available form field types including all type information
     * from the Horde_Form classes.
     *
     * @todo Doesn't work with autoloading.
     *
     * @return array  The full field types array.
     */
    static public function fieldTypes()
    {
        if (!empty(self::$_fieldTypes)) {
            return self::$_fieldTypes;
        }

        /* Fetch all declared classes. */
        $classes = get_declared_classes();

        /* Filter for the Horde_Form_Type classes. */
        $blacklist = array('invalid', 'addresslink', 'spacer', 'description',
                           'captcha', 'figlet', 'header');
        foreach ($classes as $class) {
            if (stripos($class, 'horde_form_type_') !== false) {
                $field_type = substr($class, 16);
                /* Don't bother including the types that cannot be handled
                 * usefully. */
                if (in_array($field_type, $blacklist)) {
                    continue;
                }
                self::$_fieldTypes[$field_type] = @call_user_func(
                    array('Horde_Form_Type_' . $field_type, 'about'));
            }
        }

        return self::$_fieldTypes;
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
     * Returns the parameters necessary to run an address search.
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
