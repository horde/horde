<?php
/**
 * Whups external API interface.
 *
 * This file defines Whups' external API interface. Other applications
 * can interact with Whups through this API.
 *
 * @package Whups
 */
class Whups_Api extends Horde_Registry_Api
{
    /**
     * Browse through Whups' object tree.
     *
     * @param string $path  The level of the tree to browse.
     *
     * @return array  The contents of $path
     */
    public function browse($path = '')
    {
        global $whups_driver, $registry;

        if (substr($path, 0, 5) == 'whups') {
            $path = substr($path, 5);
        }
        $path = trim($path, '/');

        if (empty($path)) {
            $results = array(
                'whups/queue' => array(
                    'name' => _("Queues"),
                    'icon' => Horde_Themes::img('whups.png'),
                    'browseable' => count(
                        Whups::permissionsFilter($whups_driver->getQueues(),
                                                 'queue', Horde_Perms::READ))));
        } else {
            $path = explode('/', $path);
            Horde::logMessage(var_export($path, true), 'INFO');
            $results = array();

            switch ($path[0]) {
            case 'queue':
                $queues = Whups::permissionsFilter($whups_driver->getQueues(),
                                                   'queue', Horde_Perms::SHOW);
                if (count($path) == 1) {
                    foreach ($queues as $queue => $name) {
                        $results['whups/queue/' . $queue] = array(
                            'name' => $name,
                            'browseable' => true);
                    }
                } else {
                    if (!Whups::hasPermission($queues[$path[1]], 'queue',
                                              Horde_Perms::READ)) {
                        return PEAR::raiseError('permission denied');
                    }

                    $tickets = $whups_driver->getTicketsByProperties(
                        array('queue' => $path[1]));
                    foreach ($tickets as $ticket) {
                        $results['whups/queue/' . $path[1] . '/' . $ticket['id']] = array(
                            'name' => $ticket['summary'],
                            'browseable' => false);
                    }
                }
                break;
            }
        }

        return $results;
    }

    /**
     * Create a new queue.
     *
     * @param string $name The queue's name.
     *
     * @return integer  The new queue id.
     */
    public function addQueue($name)
    {
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin'))) {
            return $GLOBALS['whups_driver']->addQueue($name, '');
        } else {
            return PEAR::raiseError('You must be an administrator to perform this action.');
        }
    }

    /**
     * Return the ids of all open tickets assigned to the current user.
     *
     * @return array  Array of ticket ids.
     */
    public function getAssignedTicketIds()
    {
        global $whups_driver;

        $info = array('owner' => 'user:' . $GLOBALS['registry']->getAuth(), 'nores' => true);
        $tickets = $whups_driver->getTicketsByProperties($info);
        if (is_a($tickets, 'PEAR_Error')) {
            return $tickets;
        }
        $result = array();
        foreach ($tickets as $ticket) {
            $result[] = $ticket['id'];
        }
        return $result;
    }

    /**
     * Return the ids of all open tickets that the current user created.
     *
     * @return array  Array of ticket ids.
     */
    public function getRequestedTicketIds()
    {
        global $whups_driver;

        $info = array('requester' => $GLOBALS['registry']->getAuth(), 'nores' => true);
        $tickets = $whups_driver->getTicketsByProperties($info);
        if (is_a($tickets, 'PEAR_Error')) {
            return $tickets;
        }
        $result = array();
        foreach ($tickets as $ticket) {
            $result[] = (int) $ticket['id'];
        }
        return $result;
    }

    /**
     * Create a new ticket.
     *
     * @param array $ticket_info An array of form variables representing all of the
     * data collected by CreateStep1Form, CreateStep2Form, CreateStep3Form, and
     * optionally CreateStep4Form.
     *
     * @return integer The new ticket id.
     */
    public function addTicket($ticket_info)
    {
        require_once dirname(__FILE__) . '/Forms/CreateTicket.php';
        require_once dirname(__FILE__) . '/Ticket.php';
        global $whups_driver;

        if (!is_array($ticket_info)) {
            return PEAR::raiseError('Invalid arguments');
        }

        $vars = new Horde_Variables($ticket_info);

        $form1 = new CreateStep1Form($vars);
        $form2 = new CreateStep2Form($vars);
        $form3 = new CreateStep3Form($vars);

        // FIXME: This is an almighty hack, but we can't have form
        // tokens in rpc calls.
        $form1->useToken(false);
        $form2->useToken(false);
        $form3->useToken(false);

        // Complain if we've been given bad parameters.
        if (!$form1->validate($vars, true)) {
            $f1 = var_export($form1->_errors, true);
            return PEAR::raiseError("Invalid arguments ($f1)");
        }
        if (!$form2->validate($vars, true)) {
            $f2 = var_export($form2->_errors, true);
            return PEAR::raiseError("Invalid arguments ($f2)");
        }
        if (!$form3->validate($vars, true)) {
            $f3 = var_export($form3->_errors, true);
            return PEAR::raiseError("Invalid arguments ($f3)");
        }

        $form1->getInfo($vars, $info);
        $form2->getInfo($vars, $info);
        $form3->getInfo($vars, $info);

        // More checks if we're assigning the ticket at create-time.
        if ($GLOBALS['registry']->getAuth() && $whups_driver->isCategory('assigned', $vars->get('state'))) {
            $form4 = new CreateStep4Form($vars);
        }
        if (Auth::getAuth() && $whups_driver->isCategory('assigned', $vars->get('state'))) {
            $form4 = new CreateStep4Form($vars);
            $form4->useToken(false);
            if (!$form4->validate($vars, true)) {
                return PEAR::raiseError('Invalid arguments (' . var_export($form4->_errors, true) . ')');
            }

            $form4->getInfo($vars, $info);
        }

        $ticket = Whups_Ticket::newTicket($info, $GLOBALS['registry']->getAuth());
        if (is_a($ticket, 'PEAR_Error')) {
            return $ticket;
        } else {
            return $ticket->getId();
        }
    }

    /**
     * Update a ticket's properties.
     *
     * @param integer $ticket_id    The id of the id to changes.
     * @param array   $ticket_info  The attributes to set, from the EditTicketForm.
     *
     * @return boolean  True
     */
    public function updateTicket($ticket_id, $ticket_info)
    {
        require_once dirname(__FILE__) . '/Ticket.php';
        require_once dirname(__FILE__) . '/Forms/EditTicket.php';
        global $whups_driver;

        // Cast as an int for safety.
        $ticket = Whups_Ticket::makeTicket((int)$ticket_id);
        if (is_a($ticket, 'PEAR_Error')) {
            // The ticket is either invalid or we don't have permission to
            // read it.
            return $ticket;
        }

        // Check that we have permission to update the ticket
        if (!$GLOBALS['registry']->getAuth() ||
            !Whups::hasPermission($ticket->get('queue'), 'queue', 'update')) {
            return PEAR::raiseError(_('You do not have permission to update this ticket.'));
        }

        // Populate $vars with existing ticket details.
        $vars = new Horde_Variables();
        $ticket->setDetails($vars);

        // Copy new ticket details in.
        foreach ($ticket_info as $detail => $newval) {
            $vars->set($detail, $newval);
        }

        // Create and populate the EditTicketForm for validation. API calls can't
        // use form tokens and aren't the result of the EditTicketForm being
        // submitted.
        $editform = new EditTicketForm($vars, null, $ticket);
        $editform->useToken(false);
        $editform->setSubmitted(true);

        // Attempt to validate and update the ticket.
        if (!$editform->validate($vars)) {
             $form_errors = var_export($editform->_errors, true);
             return PEAR::raiseError(sprintf(_("Invalid ticket data supplied: %s"), $form_errors));
        }

        $editform->getInfo($vars, $info);

        $ticket->change('summary', $info['summary']);
        $ticket->change('state', $info['state']);
        $ticket->change('priority', $info['priority']);
        if (!empty($info['newcomment'])) {
            $ticket->change('comment', $info['newcomment']);
        }

        // Update attributes.
        $whups_driver->setAttributes($info);

        // Add attachment if one was uploaded.
        if (!empty($info['newattachment']['name'])) {
            $ticket->change('attachment',
                            array('name' => $info['newattachment']['name'],
                                  'tmp_name' => $info['newattachment']['tmp_name']));
        }

        // If there was a new comment and permissions were specified on
        // it, set them.
        if (!empty($info['group'])) {
            $ticket->change('comment-perms', $info['group']);
        }

        $result = $ticket->commit();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Ticket updated successfully
        return true;
    }

    /**
     * Add a comment to a ticket.
     *
     * @param integer $ticket_id  The id of the ticket to comment on.
     * @param string  $comment    The comment text to add.
     * @param string  $group      (optional) Restrict this comment to a specific group.
     *
     * @return boolean  True
     */
    public function addComment($ticket_id, $comment, $group = null)
    {
        $ticket_id = (int)$ticket_id;
        if (empty($ticket_id)) {
            return PEAR::raiseError('Invalid ticket id');
        }

        $ticket = Whups_Ticket::makeTicket($ticket_id);
        if (is_a($ticket, 'PEAR_Error')) {
            return $ticket;
        }

        if (empty($comment)) {
            return PEAR::raiseError('Empty comments are not allowed');
        }

        // Add comment.
        $ticket->change('comment', $comment);

        // Add comment permissions, if specified.
        // @TODO: validate the user is allowed to specify this group
        if (!empty($group)) {
            $ticket->change('comment-perms', $group);
        }

        $result = $ticket->commit();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Adds an attachment to a ticket.
     *
     * @param integer $ticket_id  The ticket number.
     * @param string $name        The name of the attachment.
     * @param string $data        The attachment data.
     *
     * @return mixed  True on success or PEAR_Error on failure.
     */
    public function addAttachment($ticket_id, $name, $data)
    {
        $ticket_id = (int)$ticket_id;
        if (empty($ticket_id)) {
            return PEAR::raiseError(_("Invalid Ticket Id"));
        }

        $ticket = Whups_Ticket::makeTicket($ticket_id);
        if (is_a($ticket, 'PEAR_Error')) {
            return $ticket;
        }

        if (!strlen($name) || !strlen($data)) {
            return PEAR::raiseError(_("Empty attachment"));
        }

        $tmp_name = Horde_Util::getTempFile('whups', true, $GLOBALS['conf']['tmpdir']);
        $fp = fopen($tmp_name, 'wb');
        fwrite($fp, $data);
        fclose($fp);

        $ticket->change('attachment',
                        array('name' => $name, 'tmp_name' => $tmp_name));
        $result = $ticket->commit();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Set attributes for a ticket
     *
     * @TODO fold this into the updateTicket method
     */
    public function setTicketAttributes($info)
    {
        global $whups_driver;

        if (!isset($info['ticket_id']) || !isset($info['attributes'])) {
            return PEAR::raiseError(_("Invalid arguments: Must supply a ticket number and new attributes."));
        }

        $ticket = $whups_driver->getTicketDetails($info['ticket_id']);
        if (is_a($ticket, "PEAR_Error")) {
            // Either the ticket doesn't exist or the caller didn't have
            // permission.
            return $ticket;
        }

        // Convert the RPC parameters into what we'd expect if we were
        // posting the EditAttributes form.
        $ainfo = array();
        foreach ($info['attributes'] as $attrib) {
            if (!isset($attrib['id']) || !isset($attrib['value'])) {
                return PEAR::raiseError(_("Invalid argument: Missing attribute name or value."));
            }

            $ainfo['a' . $attrib['id']] = $attrib['value'];
        }

        $ainfo['id'] = $info['ticket_id'];

        return $whups_driver->setAttributes($ainfo);
    }

    /**
     * Get the types that Whups items can be listed as.
     *
     * @return array  Array of list types.
     */
    public function getListTypes()
    {
        return array('taskHash' => true);
    }

    /**
     * Get a list of items from whups as type $type.
     *
     * @param string $type  The list type to use (@see getListTypes). Currently supported: 'taskHash'
     *
     * @return array  An array of tickets.
     */
    public function listAs($type)
    {
        switch ($type) {
        case 'taskHash':
            global $whups_driver;
            $info = array('owner' => 'user:' . $GLOBALS['registry']->getAuth(),
                          'nores' => true);
            $tickets = $whups_driver->getTicketsByProperties($info);
            if (is_a($tickets, 'PEAR_Error')) {
                return $tickets;
            }
            $result = array();
            foreach ($tickets as $ticket) {
                $view_link = Whups::urlFor('ticket', $ticket['id'], true);
                $delete_link = Whups::urlFor('ticket_action', array('delete', $ticket['id']), true);
                $complete_link = Whups::urlFor('ticket_action', array('update', $ticket['id']), true);

                $result['whups/' . $ticket['id']] = array(
                    'task_id'           => $ticket['id'],
                    'priority'          => $ticket['priority_name'],
                    'tasklist_id'       => '**EXTERNAL**',
                    'completed'         => ($ticket['state_category'] == 'resolved'),
                    'name'              => '[#' . $ticket['id'] . '] ' . $ticket['summary'],
                    'desc'              => null,
                    'due'               => null,
                    'category'          => null,
                    'view_link'         => $view_link,
                    'delete_link'       => $delete_link,
                    'edit_link'         => $view_link,
                    'complete_link'     => $complete_link
                    );
            }
            break;

        default:
            $result = array();
            break;
        }

        return $result;
    }

    /**
     * Return a list of queues that the current user has read permissions for
     *
     * @return array  Array of queue details
     */
    public function listQueues()
    {
        return Whups::permissionsFilter($GLOBALS['whups_driver']->getQueuesInternal(), 'queue', Horde_Perms::SHOW);
    }

    /**
     * Get details for a queue
     *
     * @param array | integer $queue  Either an array of queue ids or a single queue id.
     *
     * @return array  An array of queue information (or an array of arrays, if multiple queues were passed).
     */
    public function getQueueDetails($queue)
    {
        if (is_array($queue)) {
            $queues = Whups::permissionsFilter($queue, 'queue_id');
            $details = array();
            foreach ($queues as $id) {
                $details[$id] = $GLOBALS['whups_driver']->getQueueInternal($id);
            }
            return $details;
        }

        $queues = Whups::permissionsFilter(array($queue), 'queue_id');
        if ($queues) {
            return $GLOBALS['whups_driver']->getQueueInternal($queue);
        }

        return array();
    }

    /**
     * List the versions associated with a queue
     *
     * @param integer $queue  The queue id to get versions for.
     *
     * @return array  Array of queue versions
     */
    public function listVersions($queue)
    {
        $queues = Whups::permissionsFilter(array($queue), 'queue_id');
        if (!$queues) {
            return array();
        }

        $versions = array();
        $version_list = $GLOBALS['whups_driver']->getVersionInfoInternal($queue);
        foreach ($version_list as $version) {
            $versions[] = array('id' => $version['version_id'],
                                'name' => $version['version_name'],
                                'description' => $version['version_description'],
                                'active' => !empty($version['version_active']),
                                'readonly' => false);
        }

        usort($versions, array($this, '_sortVersions'));

        return $versions;
    }

    private function _sortVersions($a, $b)
    {
        $a_number = (string)(int)$a['name'][0] === $a['name'][0];
        $b_number = (string)(int)$b['name'][0] === $b['name'][0];

        if ($a_number && $b_number) {
            return version_compare($b['name'], $a['name']);
        }
        if (!$a_number && !$b_number) {
            return strcoll($b['name'], $a['name']);
        }
        return $a_number ? 1 : -1;
    }

    /**
     * Add a version to a queue
     *
     * @param integer $queue       The queue id to add the version to.
     * @param string $name         The name of the new version.
     * @param string $description  The descriptive text for the new version.
     * @param boolean $active      Whether the version is still active.
     */
    public function addVersion($queue, $name, $description, $active = true)
    {
        return $GLOBALS['whups_driver']->addVersion($queue, $name, $description, $active);
    }

    /**
     * Return the details for a queue version
     *
     * @param integer $version_id  The version to fetch
     *
     * @return array  Array of version details
     */
    public function getVersionDetails($version_id)
    {
        return $GLOBALS['whups_driver']->getVersionInternal($version_id);
    }

    /**
     * Get the all tickets for a queue, optionally with a specific state.
     *
     * @param integer $queue_id  The queue to get tickets for
     * @param string  $state     The state filter, if any.
     *
     * @return array  Array of tickets
     */
    public function getTicketDetails($queue_id, $state = null)
    {
        global $whups_driver;

        $info['queue_id'] = $queue_id;
        if (!empty($state)) {
            $info['category'] = $state;
        }
        $tickets = $whups_driver->getTicketsByProperties($info);

        for ($i = 0; $i < count($tickets); $i++) {
            $view_link = Whups::urlFor('ticket', $tickets[$i]['id'], true);
            $delete_link = Whups::urlFor('ticket_action', array('delete', $tickets[$i]['id']), true);
            $complete_link = Whups::urlFor('ticket_action', array('update', $tickets[$i]['id']), true);

            $tickets[$i] = array(
                    'ticket_id'         => $tickets[$i]['id'],
                    'completed'         => ($tickets[$i]['state_category'] == 'resolved'),
                    'assigned'          => ($tickets[$i]['state_category'] == 'assigned'),
                    'name'              => $tickets[$i]['queue_name'] . ' #' .
                                           $tickets[$i]['id'] . ' - ' . $tickets[$i]['summary'],
                    'state'             => $tickets[$i]['state_name'],
                    'type'              => $tickets[$i]['type_name'],
                    'priority'          => $tickets[$i]['priority_name'],
                    'desc'              => null,
                    'due'               => null,
                    'category'          => null,
                    'view_link'         => $view_link,
                    'delete_link'       => $delete_link,
                    'edit_link'         => $view_link,
                    'complete_link'     => $complete_link
                    );
        }

        return $tickets;
    }

    /**
     * List cost objects
     *
     * @param array $criteria  The list criteria
     *
     * @return array  Tickets (as cost objects) matching $criteria
     */
    public function listCostObjects($criteria)
    {
        global $whups_driver;

        $info = array();
        if (!empty($criteria['user'])) {
            $info['owner'] = 'user:' . $GLOBALS['registry']->getAuth();
        }
        if (!empty($criteria['active'])) {
            $info['nores'] = true;
        }
        if (!empty($criteria['id'])) {
            $info['id'] = $criteria['id'];
        }

        $tickets = $whups_driver->getTicketsByProperties($info);
        if (is_a($tickets, 'PEAR_Error')) {
            return $tickets;
        }
        $result = array();
        foreach ($tickets as $ticket) {
            $result[$ticket['id']] = array('id'     => $ticket['id'],
                                           'active' => ($ticket['state_category'] != 'resolved'),
                                           'name'   => sprintf(_("Ticket %s - %s"),
                                                               $ticket['id'],
                                                               $ticket['summary']));

            /* If the user has an estimate attribute, use that for cost object
             * hour estimates. */
            $attributes = $whups_driver->getTicketAttributesWithNames($ticket['id']);
            if (!is_a($attributes, 'PEAR_Error')) {
                foreach ($attributes as $k => $v) {
                    if (strtolower($k) == _("estimated time")) {
                        if (!empty($v)) {
                            $result[$ticket['id']]['estimate'] = (double) $v;
                        }
                    }
                }
            }
        }
        ksort($result);
        if (count($result) == 0) {
            return array();
        } else {
            return array(array('category' => _("Tickets"),
                               'objects'  => array_values($result)));
        }
    }

    /**
     * List the ways that tickets can be treated as time objects
     *
     * @return array  Array of time object types
     */
    public function listTimeObjectCategories()
    {
        return array('created' => _("My tickets by creation date"),
                     'assigned' => _("My tickets by assignment date"),
                     'due' => _("My tickets by due date"),
                     'resolved' => _("My tickets by resolution date"));
    }

    /**
     * Lists tickets with due dates as time objects.
     *
     * @param array $categories  The time categories (from listTimeObjectCategories) to list.
     * @param mixed $start       The start date of the period.
     * @param mixed $end         The end date of the period.
     */
    public function listTimeObjects($categories, $start, $end)
    {
        global $whups_driver;

        $start = new Horde_Date($start);
        $start_ts = $start->timestamp();
        $end = new Horde_Date($end);
        $end_ts = $end->timestamp();

        $criteria['owner'] = Whups::getOwnerCriteria($GLOBALS['registry']->getAuth());

        /* @TODO Use $categories */
        $category = 'due';
        switch ($category) {
        case 'assigned':
            $label = _("Assigned");
            $criteria['ass'] = true;
            break;

        case 'created':
            $label = _("Created");
            break;

        case 'due':
            $label = _("Due");
            $criteria['nores'] = true;
            break;

        case 'resolved':
            $label = _("Resolved");
            $criteria['res'] = true;
            break;
        }

        $tickets = $whups_driver->getTicketsByProperties($criteria);
        if (is_a($tickets, 'PEAR_Error')) {
            return array();
        }

        $objects = array();
        foreach ($tickets as $ticket) {
            switch ($category) {
            case 'assigned':
                $t_start = $ticket['date_assigned'];
                break;

            case 'created':
                $t_start = $ticket['timestamp'];
                break;

            case 'due':
                if (empty($ticket['due'])) {
                    continue 2;
                }
                $t_start = $ticket['due'];
                break;

            case 'resolved':
                $t_start = $ticket['date_resolved'];
                break;
            }

            if ($t_start + 1 < $start_ts || $t_start > $end_ts) {
                continue;
            }
            $t = new Whups_Ticket($ticket['id'], $ticket);
            $objects[$ticket['id']] = array(
                'title' => sprintf('%s: [#%s] %s', $label, $ticket['id'], $ticket['summary']),
                'description' => $t->toString(),
                'id' => $ticket['id'],
                'start' => date('Y-m-d\TH:i:s', $t_start),
                'end' => date('Y-m-d\TH:i:s', $t_start + 1),
                'params' => array('id' => $ticket['id']),
                'link' => Whups::urlFor('ticket', $ticket['id'], true));
        }

        return $objects;
    }

}
