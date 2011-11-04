<?php
/**
 * Basic Ticket Editing form.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */
class Whups_Form_Ticket_Edit extends Horde_Form
{
    public function __construct(&$vars, &$ticket, $title = '')
    {
        global $whups_driver;

        parent::__construct($vars, $title);
        $type = $vars->get('type');

        $start_year = date('Y');
        if (is_numeric($d = $vars->get('due'))) {
            $start_year = min($start_year, date('Y', $d));
        }

        $fields = array('summary');

        $qinfo = $whups_driver->getQueue($vars->get('queue'));
        if (!empty($qinfo['versioned'])) {
            $fields[] = 'version';
        }

        $fields = array_merge($fields, array('state', 'priority', 'due'));
        try {
            $attributes = $ticket->addAttributes();
        } catch (Whups_Exception $e) {
            $attributes = array();
        }
        foreach ($attributes as $attribute) {
            $fields[] = 'attribute_' . $attribute['id'];
        }
        $fields = array_merge($fields, array('owner', 'attachments', 'comment'));

        $grouped_fields = array($fields);
        $grouped_hook = false;
        try {
            $grouped_fields = Horde::callHook(
                'group_fields',
                array($ticket->get('type'), $fields),
                'whups');
            $grouped_hook = true;
        } catch (Horde_Exception_HookNotSet $e) {
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        $this->addHidden('', 'id', 'int', true, true);
        $this->addHidden('', 'type', 'int', true, true);

        foreach ($grouped_fields as $header => $fields) {
            if ($grouped_hook) {
                $this->addVariable($header, null, 'header', false);
            }
            foreach ($fields as $field) {
                switch ($field) {
                case 'summary':
                    $this->addVariable(_("Summary"), 'summary', 'text', true);
                    break;

                case 'version':
                    $versions = $whups_driver->getVersions($vars->get('queue'));
                    if (count($versions) == 0) {
                        $vtype = 'invalid';
                        $v_params = array(_("This queue requires that you specify a version, but there are no versions associated with it. Until versions are created for this queue, you will not be able to create tickets."));
                    } else {
                        $vtype = 'enum';
                        $v_params = array($versions);
                    }
                    $this->addVariable(_("Queue Version"), 'version', $vtype, true, false, null, $v_params);
                    break;

                case 'state':
                    $this->addVariable(
                        _("State"), 'state', 'enum', true, false, null,
                        array($whups_driver->getStates($type)));
                    break;

                case 'priority':
                    $this->addVariable(
                        _("Priority"), 'priority', 'enum', true, false, null,
                        array($whups_driver->getPriorities($type)));
                    break;

                case 'due':
                    $this->addVariable(
                        _("Due Date"), 'due', 'datetime', false, false, null,
                        array($start_year));
                    break;

                case 'owner':
                    if (Whups::hasPermission($vars->get('queue'), 'queue', 'assign')) {
                        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
                        $mygroups = $groups->listAll($GLOBALS['conf']['prefs']['assign_all_groups'] ? null : $GLOBALS['registry']->getAuth());
                        asort($mygroups);

                        $f_users = array();
                        $users = $whups_driver->getQueueUsers($vars->get('queue'));
                        foreach ($users as $user) {
                            $f_users['user:' . $user] = Whups::formatUser($user);
                        }

                        $f_groups = array();
                        if ($mygroups) {
                            foreach (array_keys($mygroups) as $id) {
                                $f_groups['group:' . $id] = $groups->getName($id);
                            }
                        }

                        if (count($f_users)) {
                            asort($f_users);
                            $this->addVariable(
                                _("Owners"),
                               'owners',
                               'multienum',
                               false, false, null,
                               array($f_users));
                        }

                        if (count($f_groups)) {
                            asort($f_groups);
                            $this->addVariable(
                                _("Group Owners"),
                                'group_owners',
                                'multienum',
                                false, false,
                                null,
                                array($f_groups));
                        }
                    }
                    break;

                case 'attachments':
                    $this->addVariable(
                        _("Attachment"), 'newattachment', 'file', false);
                    break;

                case 'comment':
                    $cvar = &$this->addVariable(
                        _("Comment"), 'newcomment', 'longtext', false);

                    /* Form replies. */
                    try {
                        $replies = Whups::permissionsFilter(
                            $whups_driver->getReplies($type), 'reply');
                    } catch (Whups_Exception $e) {
                        $replies = array();
                    }
                    if (count($replies)) {
                        $params = array();
                        foreach ($replies as $key => $reply) {
                            $params[$key] = $reply['reply_name'];
                        }
                        $rvar = &$this->addVariable(
                            _("Form Reply:"), 'reply', 'enum', false, false,
                             null, array($params, true));
                        $rvar->setAction(Horde_Form_Action::factory('reload'));
                        if ($vars->get('reply')) {
                            $reply = $vars->get('newcomment');
                            if (strlen($reply)) {
                                $reply .= "\n\n";
                            }
                            $reply .= $replies[$vars->get('reply')]['reply_text'];
                            $vars->set('newcomment', $reply);
                            $vars->remove('reply');
                        }
                    }

                    /* Comment permissions. */
                    $groups = $GLOBALS['injector']->getInstance('Horde_Group');
                    $mygroups = $groups->listGroups($GLOBALS['registry']->getAuth());
                    if ($mygroups) {
                        foreach (array_keys($mygroups) as $gid) {
                            $grouplist[$gid] = $groups->getName($gid, true);
                        }
                        asort($grouplist);
                        $grouplist = array(0 => _("This comment is visible to everyone")) + $grouplist;
                        $this->addVariable(
                            _("Make this comment visible only to members of a group?"), 'group',
                            'enum', false, false, null, array($grouplist));
                    }
                    break;

                default:
                    /* Ticket attributes. */
                    if ($ticket &&
                        substr($field, 0, 10) == 'attribute_' &&
                        isset($attributes[substr($field, 10)])) {
                        $attribute = $attributes[substr($field, 10)];
                        $var = $this->addVariable(
                            $attribute['human_name'],
                            'attribute_' . $attribute['id'],
                            $attribute['type'],
                            $attribute['required'],
                            $attribute['readonly'],
                            $attribute['desc'],
                            $attribute['params']);
                        $var->setDefault($attribute['value']);
                    }
                }
            }
        }
    }

    public function validate(&$vars)
    {
        if (!$GLOBALS['registry']->getAuth()) {
            $this->setError('_auth', _("Permission Denied."));
        }

        return parent::validate($vars);
    }

}
