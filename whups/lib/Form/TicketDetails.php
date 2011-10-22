<?php
/**
 * @package Whups
 */
class Whups_Form_TicketDetails extends Horde_Form
{
    public $attributes = array();

    /**
     */
    public function __construct(&$vars, &$ticket, $title = '')
    {
        parent::__construct($vars, $title);

        $date_params = array($GLOBALS['prefs']->getValue('date_format'));
        $fields = array('summary', 'queue', 'version', 'type', 'state',
                        'priority', 'owner', 'requester', 'created', 'due',
                        'updated', 'assigned', 'resolved', 'attachments');
        try {
            $attributes = $ticket->addAttributes();
        } catch (Whups_Exception $e) {
            $attributes = array();
        }

        foreach ($attributes as $attribute) {
            $fields[] = 'attribute_' . $attribute['id'];
        }

        $grouped_fields = array($fields);
        $grouped_hook = false;
        try {
            $grouped_fields = Horde::callHook(
                'group_fields', array($ticket->get('type'), $fields), 'whups');
            $grouped_hook = true;
        } catch (Horde_Exception_HookNotSet $e) {
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        foreach ($grouped_fields as $header => $fields) {
            if ($grouped_hook) {
                $this->addVariable($header, null, 'header', false);
            }
            foreach ($fields as $field) {
                switch ($field) {
                case 'summary':
                    $this->addVariable(_("Summary"), 'summary', 'text', true);
                    break;

                case 'queue':
                    if ($vars->get('queue_link')) {
                        $this->addVariable(
                            _("Queue"), 'queue_link', 'link', true, false, null,
                            array(
                                array(
                                    'url' => $vars->get('queue_link'),
                                    'text' => $vars->get('queue_name'))));
                    } else {
                        $this->addVariable(
                            _("Queue"), 'queue_name', 'text', true);
                    }
                    break;

                case 'version':
                    if ($vars->get('version_name')) {
                        if ($vars->get('version_link')) {
                            $this->addVariable(
                                _("Queue Version"), 'version_name', 'link',
                                true, false, null,
                                array(
                                    array(
                                        'url' => $vars->get('version_link'),
                                        'text' => $vars->get('version_name'))));
                        } else {
                            $this->addVariable(
                                _("Queue Version"), 'version_name', 'text', true);
                        }
                    }
                    break;

                case 'type':
                    $this->addVariable(_("Type"), 'type_name', 'text', true);
                    break;

                case 'state':
                    $this->addVariable(_("State"), 'state_name', 'text', true);
                    break;

                case 'priority':
                    $this->addVariable(
                        _("Priority"), 'priority_name', 'text', true);
                    break;

                case 'owner':
                    $owner = &$this->addVariable(
                        _("Owners"), 'user_id_owner', 'email', false, false,
                         null, array(false, true));
                    $owner->setDefault(_("Unassigned"));
                    break;

                case 'requester':
                    $this->addVariable(
                        _("Requester"), 'user_id_requester', 'email', false,
                         false, null, array(false, true));
                    break;

                case 'created':
                    $this->addVariable(
                        _("Created"), 'timestamp', 'date', false, false, null,
                        $date_params);
                    break;

                case 'due':
                    $this->addVariable(
                        _("Due"), 'due', 'datetime', false, false, null,
                        $date_params);
                    break;

                case 'updated':
                    $this->addVariable(
                        _("Updated"), 'date_updated', 'date', false, false,
                        null, $date_params);
                    break;

                case 'assigned':
                    $this->addVariable(
                        _("Assigned"), 'date_assigned', 'date', false, false,
                        null, $date_params);
                    break;

                case 'resolved':
                    $this->addVariable(
                        _("Resolved"), 'date_resolved', 'date', false, false,
                        null, $date_params);
                    break;

                case 'attachments':
                    $this->addVariable(
                        _("Attachments"), 'attachments', 'html', false);
                    break;

                default:
                    if (substr($field, 0, 10) == 'attribute_' &&
                        isset($attributes[substr($field, 10)])) {
                        $attribute = $attributes[substr($field, 10)];
                        if (!$attribute['params']) {
                            $attribute['params'] = array();
                        }
                        $this->attributes[$attribute['id']] = $this->addVariable(
                            $attribute['human_name'],
                            'attribute_' . $attribute['id'],
                            $attribute['type'], $attribute['required'],
                            $attribute['readonly'], $attribute['desc'],
                            $attribute['params']);
                        $this->attributes[$attribute['id']]->setDefault($attribute['value']);
                    }
                    break;
                }
            }
        }
    }

}
