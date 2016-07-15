<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * Renders the attendees popup in basic mode.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_View_Attendees extends Horde_View
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        global $conf, $injector, $registry, $session;

        $config['templatePath'] = KRONOLITH_TEMPLATES . '/attendees';
        parent::__construct($config);
        $this->addHelper('Text');

        $this->formInput = Horde_Util::formInput();
        $this->view = Horde_Util::getFormData('view', 'Day');
        $this->date = $config['start']->dateString();
        $this->end  = $config['end']->dateString();
        $this->freeBusy = $config['fbView']->render($config['start']);
        $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
        if ($auth->hasCapability('list') &&
            ($conf['auth']['list_users'] == 'list' ||
             $conf['auth']['list_users'] == 'both')) {
            $this->userList = $auth->listNames();
        }
        $this->resourcesEnabled = !empty($conf['resources']['enabled']);
        if ($registry->hasMethod('contacts/search')) {
            $this->addressbookLink = Horde::url('#')
                ->link(array(
                    'class' => 'widget',
                    'onclick' => 'window.open(\'' . Horde::url('contacts.php')
                        . '\', \'contacts\', \'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=270,left=100,top=100\'); return false;'
                ))
                . Horde::img('addressbook_browse.png') . '<br />'
                . _("Address Book") . '</a>';
        }

        $vars = Horde_Variables::getDefaultVariables();
        $tabs = new Horde_Core_Ui_Tabs(null, $vars);
        $tabs->addTab(
            _("Day"),
            new Horde_Url('javascript:switchView(\'Day\')'),
            'Day'
        );
        $tabs->addTab(
            _("Work Week"),
            new Horde_Url('javascript:switchView(\'Workweek\')'),
            'Workweek'
        );
        $tabs->addTab(
            _("Week"),
            new Horde_Url('javascript:switchView(\'Week\')'),
            'Week'
        );
        $tabs->addTab(
            _("Month"),
            new Horde_Url('javascript:switchView(\'Month\')'),
            'Month'
        );
        $this->tabs = $tabs->render($this->view);

        $attendees = $session->get('kronolith', 'attendees');
        if ($attendees) {
            $roles = array(
                Kronolith::PART_REQUIRED,
                Kronolith::PART_OPTIONAL,
                Kronolith::PART_NONE
            );
            $responses = array(
                Kronolith::RESPONSE_ACCEPTED,
                Kronolith::RESPONSE_DECLINED,
                Kronolith::RESPONSE_TENTATIVE,
                Kronolith::RESPONSE_NONE
            );
            $this->attendees = array();
            foreach ($attendees as $attendee) {
                $viewAttendee = array(
                    'id' => $attendee->id,
                    'name' => strval($attendee),
                    'deleteLink' => Horde::url('#')
                        ->link(array(
                            'title' => sprintf(
                                _("Remove %s"), $attendee->displayName
                            ),
                            'onclick' => "performAction('remove', decodeURIComponent('" . rawurlencode($attendee->id) . "')); return false;"
                        ))
                        . Horde::img('delete.png') . '</a>',
                );
                if ($attendee->user) {
                    unset($this->userList[$attendee->user]);
                } else {
                    $viewAttendee['editLink'] = Horde::url('#')
                        ->link(array(
                            'title' => sprintf(
                                _("Edit %s"), $attendee->displayName
                            ),
                            'onclick' => "performAction('edit', decodeURIComponent('" . rawurlencode($attendee->id) . "')); return false;"
                        ))
                        . Horde::img('edit.png') . '</a>';
                }
                foreach ($roles as $role) {
                    $viewAttendee['roles'][$role] = array(
                        'selected' => $attendee->role == $role,
                        'label' => Kronolith::partToString($role),
                    );
                }
                foreach ($responses as $response) {
                    $viewAttendee['responses'][$response] = array(
                        'selected' => $attendee->response == $response,
                        'label' => Kronolith::responseToString($response),
                    );
                }
                $this->attendees[] = $viewAttendee;
            }
        }

        $this->resources = $session->get(
            'kronolith', 'resources', Horde_Session::TYPE_ARRAY
        );
        foreach ($this->resources as $id => &$resource) {
            $resource['id'] = $id;
            $resource['deleteLink'] = Horde::url('#')
                ->link(array(
                    'title' => sprintf(_("Remove %s"), $resource['name']),
                    'onclick' => "performAction('removeResource', decodeURIComponent('" . $id . "')); return false;"
                ))
                . Horde::img('delete.png') . '</a>';
            foreach ($roles as $role) {
                $resource['roles'][$role] = array(
                    'selected' => $resource['attendance'] == $role,
                    'label' => Kronolith::partToString($role),
                );
            }
            foreach ($responses as $response) {
                $resource['responses'][$response] = array(
                    'selected' => $resource['response'] == $response,
                    'label' => Kronolith::responseToString($response),
                );
            }
        }

        /* Get list of resources for select list, and remove those we already
         * added. */
        if ($this->resourcesEnabled) {
            $this->allResources = array_diff_key(
                Kronolith::getDriver('Resource')
                    ->listResources(Horde_Perms::READ, array(), 'name'),
                $this->resources
            );
        }
    }
}
