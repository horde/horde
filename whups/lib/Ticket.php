<?php
/**
 * The Whups_Ticket class encapsulates some logic relating to tickets, sending
 * updates, etc.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */
class Whups_Ticket {

    /**
     * The id of the ticket this object wraps.
     *
     * @var integer
     */
    var $_id;

    /**
     * The current values of the ticket.
     *
     * @var array
     */
    var $_details;

    /**
     * Array of changes to make to the ticket.
     *
     * @var array
     */
    var $_changes = array();

    /**
     * Returns a ticket object for an id.
     *
     * @static
     *
     * @param integer $id  The ticket id.
     *
     * @return Whups_Ticket  Either a Whups_Ticket object on success or a
     *                       PEAR_Error object on failure.
     */
    function makeTicket($id)
    {
        global $whups_driver;

        $details = $whups_driver->getTicketDetails($id);
        if (is_a($details, 'PEAR_Error')) {
            return $details;
        } else {
            $ticket = new Whups_Ticket($id, $details);
            return $ticket;
        }
    }

    /**
     * Creates a new ticket.
     *
     * Pretty bare wrapper around Whups_Driver::addTicket().
     *
     * @static
     *
     * @param array $info  A hash with ticket information.
     *
     * @return Whups_Ticket  Either a Whups_Ticket object on success or a
     *                       PEAR_Error object on failure.
     */
    function newTicket($info, $requester)
    {
        global $whups_driver;

        if (!isset($info['type'])) {
            $info['type'] = $whups_driver->getDefaultType($info['queue']);
            if (is_null($info['type'])) {
                $queue = $whups_driver->getQueue($info['queue']);
                return PEAR::raiseError(
                    sprintf(_("No type for this ticket and no default type for queue \"%s\" specified."),
                            $queue['name']));
            }
        }
        if (!isset($info['state'])) {
            $info['state'] = $whups_driver->getDefaultState($info['type']);
            if (is_null($info['state'])) {
                return PEAR::raiseError(
                    sprintf(_("No state for this ticket and no default state for ticket type \"%s\" specified."),
                            $whups_driver->getTypeName($info['type'])));
            }
        }
        if (!isset($info['priority'])) {
            $info['priority'] = $whups_driver->getDefaultPriority($info['type']);
            if (is_null($info['priority'])) {
                return PEAR::raiseError(
                    sprintf(_("No priority for this ticket and no default priority for ticket type \"%s\" specified."),
                            $whups_driver->getTypeName($info['type'])));
            }
        }

        $id = $whups_driver->addTicket($info, $requester);
        if (is_a($id, 'PEAR_Error')) {
            return $id;
        }

        $details = $whups_driver->getTicketDetails($id, false);
        if (is_a($details, 'PEAR_Error')) {
            return $details;
        }

        $ticket = new Whups_Ticket($id, $details);

        // Add attachment if one was uploaded.
        if (!empty($info['newattachment']['name'])) {
            $ticket->change(
                'attachment',
                array('name' => $info['newattachment']['name'],
                      'tmp_name' => $info['newattachment']['tmp_name']));
        }

        // Check for a deferred attachment upload.
        if (!empty($info['deferred_attachment']) &&
            ($a_name = $GLOBALS['session']->get('whups', 'deferred_attachment/' . $info['deferred_attachment']))) {
            $ticket->change(
                'attachment',
                array('name' => $info['deferred_attachment'],
                      'tmp_name' => $a_name));

            unlink($a_name);
        }

        // Send email notifications.
        $ticket->notify($ticket->get('user_id_requester'), true);

        // Commit any changes (new attachments, etc.)
        $ticket->commit($ticket->get('user_id_requester'),
                        $info['last-transaction'], false);

        return $ticket;
    }

    /**
     * Constructor.
     *
     * @param integer $id     The ticket id.
     * @param array $details  The hash of ticket information.
     */
    function Whups_Ticket($id, $details)
    {
        $this->_id = $id;
        $this->_details = $details;
    }

    /**
     * Returns all ticket information.
     *
     * @return array  The ticket information.
     */
    function getDetails()
    {
        return $this->_details;
    }

    /**
     * Returns the ticket id.
     *
     * @return integer  The ticket id.
     */
    function getId()
    {
        return $this->_id;
    }

    /**
     * Returns a piece of information from this ticket.
     *
     * @param string $detail  The detail to return.
     *
     * @return mixed  The detail value.
     */
    function get($detail)
    {
        return isset($this->_details[$detail])
            ? $this->_details[$detail]
            : null;
    }

    /**
     * Changes a detail of the ticket to a new value.
     *
     * Never touches the backend; do not use for changes that you want to
     * persist.
     *
     * @param string $detail  The detail to change.
     * @param string $value   The new detail value.
     */
    function set($detail, $value)
    {
        $this->_details[$detail] = $value;
    }

    /**
     * Tracks that a detail of the ticket should change, but does not actually
     * make the change until commit() is called.
     *
     * @see commit()
     *
     * @param string $detail  The detail to change.
     * @param string $value   The new detail value.
     */
    function change($detail, $value)
    {
        $previous_value = isset($this->_details[$detail])
            ? $this->_details[$detail]
            : '';
        if ($previous_value != $value) {
            $this->_changes[$detail] = array(
                'from' => $this->get($detail),
                'from_name' => $this->get($detail . '_name'),
                'to' => $value);
        }
    }

    /**
     * Goes through a list of built-up changes and commits them to the
     * backend.
     *
     * This will send email updates by default, update the ticket log, etc.
     *
     * @see change()
     *
     * @param string  $user         The Horde user of the changes to be made.
     *                              Defaults to the current user.
     * @param integer $transaction  The transaction these changes are part of.
     *                              Defaults to a new transaction.
     * @param boolean $notify       Send ticket notifications?
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function commit($user = null, $transaction = null, $notify = true)
    {
        global $whups_driver;

        if (!count($this->_changes)) {
            return true;
        }

        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }
        $author_email = isset($this->_changes['comment-email']['to'])
            ? $this->_changes['comment-email']['to']
            : null;

        if (is_null($transaction)) {
            // Get a new transaction id from the backend.
            $transaction = $whups_driver->newTransaction($user, $author_email);
            if (is_a($transaction, 'PEAR_Error')) {
                return $transaction;
            }
        }

        // If this is a guest update, the comment id is going to map to the
        // requester pseudo-username.
        if ($user === false) {
            $user = '-' . $transaction . '_transaction';
        }

        // Run hook before setting the dates.
        try {
            $this->_changes = Horde::callHook('ticket_update', array($this, $this->_changes), 'whups');
        } catch (Horde_Exception_HookNotSet $e) {
        }

        // Update cached dates.
        $timestamp = time();
        $this->_changes['date_updated'] = array('to' => $timestamp);
        if (isset($this->_changes['state'])) {
            $state = $whups_driver->getState($this->_changes['state']['to']);
            if (is_a($state, 'PEAR_Error')) {
                return $state;
            }
            if ($state['category'] == 'assigned') {
                $this->_changes['date_assigned'] = array('to' => $timestamp);
                $this->_changes['date_resolved'] = array('to' => null);
            } elseif ($state['category'] == 'resolved') {
                $this->_changes['date_resolved'] = array('to' => $timestamp);
            } else {
                $this->_changes['date_resolved'] = array('to' => null);
            }
        }

        $updates = array();
        foreach ($this->_changes as $detail => $values) {
            $value = $values['to'];
            switch ($detail) {
            case 'owners':
                // Fetch $oldOwners list; then loop through $value adding and
                // deleting as needed.
                $oldOwners = $whups_driver->getOwners($this->_id);
                $this->_changes['oldowners'] = $oldOwners;

                foreach ($value as $owner) {
                    if (empty($oldOwners[$owner])) {
                        $whups_driver->addTicketOwner($this->_id, $owner);
                        $whups_driver->updateLog($this->_id, $user,
                                                 array('assign' => $owner),
                                                 $transaction);
                    } else {
                        // Remove $owner from the old owners list; anyone left
                        // in $oldOwners will be removed.
                        unset($oldOwners[$owner]);
                    }
                }

                // Delete removed owners and log the removals.
                if (is_array($oldOwners)) {
                    foreach ($oldOwners as $owner) {
                        $whups_driver->deleteTicketOwner($this->_id, $owner);
                        $whups_driver->updateLog($this->_id, $user,
                                                 array('unassign' => $owner),
                                                 $transaction);
                    }
                }
                break;

            case 'comment':
                $commentId = $whups_driver->addComment($this->_id, $value,
                                                       $user, $author_email);
                if (is_a($commentId, 'PEAR_Error')) {
                    return $commentId;
                }

                // Store the comment id in the updates array for the log.
                $updates['comment'] = $commentId;
                if (!empty($this->_changes['comment-perms'])) {
                    $this->addCommentPerms(
                        $commentId,
                        $this->_changes['comment-perms']['to']);
                }
                break;

            case 'comment-email':
            case 'comment-perms':
                // Skip these, handled in the comment case.
                break;

            case 'attachment':
                $result = $this->addAttachment($value['name'],
                                               $value['tmp_name']);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                } else {
                    // Store the new file name in the updates array for the
                    // log.
                    $updates['attachment'] = $value['name'];
                }
                break;

            case 'delete-attachment':
                $result = $this->deleteAttachment($value);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                } else {
                    // Store the deleted file name in the updates array for
                    // the log.
                    $updates['delete-attachment'] = $value;
                }
                break;

            case 'queue':
                // Reset version if new queue is not versioned.
                $newqueue = $whups_driver->getQueue($value);
                if (empty($newqueue['queue_versioned'])) {
                    $updates['version'] = 0;
                }
                $updates['queue'] = $value;

            default:
                $updates[$detail] = $value;
            }
        }

        if (count($updates)) {
            $result = $whups_driver->updateTicket($this->_id, $updates);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }

            $result = $whups_driver->updateLog($this->_id, $user, $updates,
                                               $transaction);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        // Reload $this->_details to make sure we have the latest information.
        //
        // @todo Only touch the db if we have to.
        $details = $whups_driver->getTicketDetails($this->_id);
        if (is_a($details, 'PEAR_Error')) {
            return $details;
        }
        $this->_details = array_merge($this->_details, $details);

        // Send notification emails to all ticket listeners.
        if ($notify) {
            $this->notify($user, false);
        }

        // Reset the changes array.
        $this->_changes = array();

        return true;
    }

    /**
     * Adds an attachment to this ticket.
     *
     * @param string $attachment_name  The name of the attachment.
     * @param string $attachment_file  The temporary file containing the data
     *                                 to be stored.
     *
     * @return mixed  True on success or PEAR_Error on failure.
     */
    function addAttachment(&$attachment_name, $attachment_file)
    {
        if (!isset($GLOBALS['conf']['vfs']['type'])) {
            return PEAR::raiseError(_("The VFS backend needs to be configured to enable attachment uploads."), 'horde.error');
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        // Get existing attachment names.
        $used_names = $this->listAllAttachments();

        $dir = WHUPS_VFS_ATTACH_PATH . '/' . $this->_id;
        while ((array_search($attachment_name, $used_names) !== false) ||
               $vfs->exists($dir, $attachment_name)) {
            if (preg_match('/(.*)\[(\d+)\](\.[^.]*)?$/', $attachment_name,
                           $match)) {
                $attachment_name = $match[1] . '[' . ++$match[2] . ']';
                if (isset($match[3])) {
                    $attachment_name .= $match[3];
                }
            } else {
                $dot = strrpos($attachment_name, '.');
                if ($dot === false) {
                    $attachment_name .= '[1]';
                } else {
                    $attachment_name = substr($attachment_name, 0, $dot)
                        . '[1]' . substr($attachment_name, $dot);
                }
            }
        }

        try {
            $vfs->write($dir, $attachment_name, $attachment_file, true);
            return true;
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Removes an attachment from this ticket.
     *
     * @param string $attachment_name  The name of the attachment.
     *
     * @return mixed  True on success or PEAR_Error on failure.
     */
    function deleteAttachment($attachment_name)
    {
        if (!isset($GLOBALS['conf']['vfs']['type'])) {
            return PEAR::raiseError(_("The VFS backend needs to be configured to enable attachment uploads."), 'horde.error');
        }

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        $dir = WHUPS_VFS_ATTACH_PATH . '/' . $this->_id;
        if (!$vfs->exists($dir, $attachment_name)) {
            return PEAR::raiseError(sprintf(_("Attachment %s not found."),
                                            $attachment_name),
                                    'horde.error');
        }

        try {
            $vfs->deleteFile($dir, $attachment_name);
            return true;
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Returns a list of all files that have been attached to this ticket,
     * whether they still exist or not.
     */
    function listAllAttachments()
    {
        $files = array();
        $history = $GLOBALS['whups_driver']->getHistory($this->_id);
        foreach ($history as $row) {
            if (isset($row['changes'])) {
                foreach ($row['changes'] as $change) {
                    if (isset($change['type']) &&
                        $change['type'] == 'attachment') {
                        $files[] = $change['value'];
                    }
                }
            }
        }

        return array_unique($files);
    }

    /**
     * Redirects the browser to this ticket's view.
     */
    function show()
    {
        Whups::urlFor('ticket', $this->_id, true)->redirect();
    }

    /**
     * Returns a <link> tag for this ticket's feed.
     *
     * @return string  A full <link> tag.
     */
    function feedLink()
    {
        return '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars('[#' . $this->getId() . '] ' . $this->get('summary')) . '" href="' . Whups::urlFor('ticket_rss', $this->getId(), true, -1) . '" />';
    }

    /**
     * Sets exclusive read permissions on a comment to a certain group.
     *
     * @param integer $commentId  The id of the comment to restrict.
     * @param string  $group      The group name to limit access by.
     */
    function addCommentPerms($commentId, $group)
    {
        if (!empty($group)) {
            $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
            $perm = $perms->newPermission('whups:comments:' . $commentId);
            $perm->addGroupPermission($group, Horde_Perms::READ, false);
            return $perms->addPermission($perm);
        }
    }

    /**
     * Sets all properties of the ticket necessary to display the
     * TicketDetailsForm.
     *
     * @param Horde_Variables $vars  The form variables object to set info in.
     */
    function setDetails(&$vars)
    {
        $vars->set('id', $this->getId());
        foreach ($this->getDetails() as $varname => $value) {
            $vars->set($varname, $value);
        }

        /* User formatting. */
        $vars->set('user_id_requester',
                   Whups::formatUser($this->get('user_id_requester')));
        $vars->set('user_id_owner', Whups::getOwners($this->_id));

        /* Attachments. */
        $attachments = array();
        $files = Whups::getAttachments($this->_id);
        if ($files) {
            if (is_a($files, 'PEAR_Error')) {
                $GLOBALS['notification']->push($files);
            } else {
                foreach ($files as $file) {
                    $attachments[] = Whups::attachmentUrl(
                        $this->_id, $file, $this->_details['queue']);
                }
            }
            $vars->set('attachments', implode("<br />\n", $attachments));
        }
    }

    /**
     * Notifies all appropriate people of the creation/update of this ticket.
     *
     * @param string  $author   Who created/changed the ticket?
     * @param boolean $isNew    Is this a new ticket or a change to an existing
     *                          one?
     * @param array $listeners  The list of listener that should receive the
     *                          notification. If empty, the list will be
     *                          created automatically.
     */
    function notify($author, $isNew, $listeners = array())
    {
        global $conf, $whups_driver;

        /* Get the attributes for this ticket. */
        $attributes = $whups_driver->getAttributesForType($this->get('type'));

        $fields = array(
            'queue' => _("Queue"),
            'version' => _("Version"),
            'type' => _("Type"),
            'state' => _("State"),
            'priority' => _("Priority"),
            'due' => _("Due"),
        );

        $field_names = array_merge($fields, array(_("Created By"),
                                                  _("Updated By"),
                                                  _("Summary"),
                                                  _("Owners"),
                                                  _("New Attachment"),
                                                  _("Deleted Attachment")));
        foreach ($attributes as $attribute) {
            $field_names[] = $attribute['human_name'];
        }

        /* Find the longest translated field name. */
        $length = 0;
        foreach ($field_names as $field_name) {
            $length = max($length, Horde_String::length($field_name));
        }
        $wrap_break = "\n" . str_repeat(' ', $length + 2) . '| ';
        $wrap_width = 73 - $length;

        /* Ticket URL. */
        $url = sprintf(_("Ticket URL: %s"),
                       Whups::urlFor('ticket', $this->_id, true, -1));

        /* Ticket properties. */
        $table = "------------------------------------------------------------------------------\n"
            . ' ' . Horde_String::pad(_("Ticket"), $length) . ' | '
            . $this->_id . "\n" . ' '
            . Horde_String::pad($isNew ? _("Created By") : _("Updated By"), $length)
            . ' | ' . Whups::formatUser($author) . "\n";
        if (isset($this->_changes['summary'])) {
            $table .= '-' . Horde_String::pad(_("Summary"), $length) . ' | '
                . Horde_String::wrap($this->_changes['summary']['from'],
                               $wrap_width, $wrap_break)
                . "\n" . '+' . Horde_String::pad(_("Summary"), $length) . ' | '
                . Horde_String::wrap($this->get('summary'), $wrap_width, $wrap_break)
                . "\n";
        } else {
            $table .= ' ' . Horde_String::pad(_("Summary"), $length) . ' | '
                . Horde_String::wrap($this->get('summary'), $wrap_width, $wrap_break)
                . "\n";
        }

        foreach ($fields as $field => $label) {
            if ($name = $this->get($field . '_name')) {
                if (isset($this->_changes[$field])) {
                    $table .= '-' . Horde_String::pad($label, $length) . ' | '
                        . Horde_String::wrap($this->_changes[$field]['from_name'],
                                       $wrap_width, $wrap_break)
                        . "\n" . '+' . Horde_String::pad($label, $length) . ' | '
                        . Horde_String::wrap($name, $wrap_width, $wrap_break) . "\n";
                } else {
                    $table .= ' ' . Horde_String::pad($label, $length) . ' | '
                        . Horde_String::wrap($name, $wrap_width, $wrap_break) . "\n";
                }
            }
        }

        /* Attribute changes. */
        foreach ($attributes as $id => $attribute) {
            $attribute_id = 'attribute_' . $id;
            $label = $attribute['human_name'];
            if (isset($this->_changes[$attribute_id])) {
                $table .= '-' . Horde_String::pad($label, $length) . ' | '
                    . Horde_String::wrap($this->_changes[$attribute_id]['from'],
                                   $wrap_width, $wrap_break)
                    . "\n" . '+' . Horde_String::pad($label, $length) . ' | '
                    . Horde_String::wrap($this->_changes[$attribute_id]['to'],
                                   $wrap_width, $wrap_break)
                    . "\n";
            } else {
                $table .= ' ' . Horde_String::pad($label, $length) . ' | '
                    . Horde_String::wrap($this->get($attribute_id),
                                   $wrap_width, $wrap_break)
                    . "\n";
            }
        }

        /* Show any change in ticket owners. */
        $owners = $oldOwners = Horde_String::wrap(
            Whups::getOwners($this->_id, false, true),
            $wrap_width, $wrap_break);
        if (isset($this->_changes['oldowners'])) {
            $oldOwners = Horde_String::wrap(
                Whups::getOwners($this->_id, false, true,
                                 $this->_changes['oldowners']),
                $wrap_width, $wrap_break);
        }
        if ($owners != $oldOwners) {
            $table .= '-' . Horde_String::pad(_("Owners"), $length) . ' | '
                . $oldOwners . "\n" . '+' . Horde_String::pad(_("Owners"), $length)
                . ' | ' . $owners . "\n";
        } else {
            $table .= ' ' . Horde_String::pad(_("Owners"), $length) . ' | '
                . $owners . "\n";
        }

        /* New Attachments. */
        if (isset($this->_changes['attachment'])) {
            $table .= '+' . Horde_String::pad(_("New Attachment"), $length) . ' | '
                . $this->_changes['attachment']['to']['name'] . "\n";
        }

        /* Deleted Attachments. */
        if (isset($this->_changes['delete-attachment'])) {
            $table .= '+' . Horde_String::pad(_("Deleted Attachment"), $length)
                . ' | ' . $this->_changes['delete-attachment']['to'] . "\n";
        }

        $table .= "------------------------------------------------------------------------------";

        /* Add the "do not reply" tag if we don't monitor incoming  mail. */
        if (empty($conf['mail']['reply'])) {
            $dont_reply = _("DO NOT REPLY TO THIS MESSAGE. THIS EMAIL ADDRESS IS NOT MONITORED.") . "\n\n";
        } else {
            $dont_reply = '';
        }

        /* Build message template. */
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
        $name = $identity->getValue('fullname');
        if (empty($name)) {
            $name = $GLOBALS['registry']->getAuth('bare');
        }

        /* Get queue specific notification message text, if available. */
        $message_file = WHUPS_BASE . '/config/'
            . ($isNew ? 'create_email' : 'notify_email');
        if (file_exists($message_file . '_' . $this->get('queue') . '.txt')) {
            $message_file .= '_' . $this->get('queue');
        }
        $message_file .= '.txt';

        /* Prepare message text. */
        $message = str_replace(
            array('@@ticket_url@@',
                  '@@table@@',
                  '@@dont_reply@@',
                  '@@date@@',
                  '@@auth_name@@'),
            array($url, $table, $dont_reply,
                  strftime($GLOBALS['prefs']->getValue('date_format')),
                  $name),
            file_get_contents($message_file));

        /* Include Re: if the ticket isn't new for easy
         * filtering/eyeballing. */
        $subject = $this->get('summary');
        if (!$isNew) {
            $subject = 'Re: ' . $subject;
        }

        if (empty($listeners)) {
            if ($conf['mail']['incl_resp'] ||
                !count($whups_driver->getOwners($this->_id))) {
                /* Include all responsible.  */
                $listeners = $whups_driver->getListeners($this->_id, true,
                                                         true, true);
            } else {
                /* Don't include all responsible unless ticket is assigned. */
                $listeners = $whups_driver->getListeners($this->_id, true,
                                                         true, false);
            }

            /* Notify both old and new queue users if the queue has changed. */
            if (isset($this->_changes['queue'])) {
                $listeners = array_merge(
                    $listeners,
                    $whups_driver->getQueueUsers(
                        $this->_changes['queue']['from_name']));
            }
        }

        /* Pass off to Whups_Driver::mail() to do the actual comment fetching,
         * permissions checks, etc. */
        $whups_driver->mail($this->_id, $listeners, $subject, $message, $author,
                            false, $this->get('queue'), $isNew);
    }

    /**
     * Returns a plain text representation of a ticket.
     *
     * @param string  $author  Who created/changed the ticket?
     * @param boolean $isNew   Is this a new ticket or a change to an existing
     *                         one?
     */
    function toString()
    {
        $fields = array('queue' => _("Queue"),
                        'version' => _("Version"),
                        'type' => _("Type"),
                        'state' => _("State"),
                        'priority' => _("Priority"),
                        'due' => _("Due"));

        /* Find longest translated field name. */
        $length = 0;
        foreach (array_merge($fields, array(_("Summary"), _("Owners")))
                 as $field) {
            $length = max($length, Horde_String::length($field));
        }
        $wrap_break = "\n" . str_repeat(' ', $length + 2) . '| ';
        $wrap_width = 73 - $length;

        /* Ticket properties. */
        $message = ' ' . Horde_String::pad(_("Ticket"), $length) . ' | '
            . $this->_id . "\n" . ' ' . Horde_String::pad(_("Summary"), $length)
            . ' | ' . Horde_String::wrap($this->get('summary'),
                                   $wrap_width, $wrap_break)
            . "\n";

        foreach ($fields as $field => $label) {
            if ($name = $this->get($field . '_name')) {
                $message .= ' ' . Horde_String::pad($label, $length) . ' | '
                    . Horde_String::wrap($name, $wrap_width, $wrap_break) . "\n";
            }
        }

        $message .= ' ' . Horde_String::pad(_("Owners"), $length) . ' | '
            . Horde_String::wrap(Whups::getOwners($this->_id, false, true),
                           $wrap_width, $wrap_break)
            . "\n";

        return $message;
    }

    function __toString()
    {
        return $this->toString();
    }

    /**
     * Adds ticket attribute values to the ticket's details, and returns the
     * list of attributes.
     *
     * @return array  List of ticket attribute hashes.
     */
    function addAttributes()
    {
        $attributes = $GLOBALS['whups_driver']->getAllTicketAttributesWithNames($this->getId());
        if (is_a($attributes, 'PEAR_Error')) {
            return $attributes;
        }

        foreach ($attributes as $attribute_id => $attribute) {
            $this->set('attribute_' . $attribute_id, $attribute['value']);
        }
        return $attributes;
    }

}

/**
 * @package Whups
 */
class TicketDetailsForm extends Horde_Form {

    /**
     */
    function TicketDetailsForm(&$vars, &$ticket, $title = '')
    {
        parent::Horde_Form($vars, $title);

        $date_params = array($GLOBALS['prefs']->getValue('date_format'));
        $fields = array('summary', 'queue', 'version', 'type', 'state',
                        'priority', 'owner', 'requester', 'created', 'due',
                        'updated', 'assigned', 'resolved', 'attachments');
        $attributes = $ticket->addAttributes();
        if (is_a($attributes, 'PEAR_Error')) {
            $attributes = array();
        }

        foreach ($attributes as $attribute) {
            $fields[] = 'attribute_' . $attribute['id'];
        }

        $grouped_fields = array($fields);
        $grouped_hook = false;
        try {
            $grouped_fields = Horde::callHook('group_fields', array($ticket->get('type'), $fields), 'whups');
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
                            _("Queue"), 'queue_link', 'link',
                            true, false, null,
                            array(array('url' => $vars->get('queue_link'),
                                        'text' => $vars->get('queue_name'))));
                    } else {
                        $this->addVariable(_("Queue"), 'queue_name', 'text',
                                           true);
                    }
                    break;

                case 'version':
                    if ($vars->get('version_name')) {
                        if ($vars->get('version_link')) {
                            $this->addVariable(
                                _("Queue Version"), 'version_name', 'link',
                                true, false, null,
                                array(
                                    array('url' => $vars->get('version_link'),
                                          'text' => $vars->get('version_name'))));
                        } else {
                            $this->addVariable(_("Queue Version"),
                                               'version_name', 'text', true);
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
                    $this->addVariable(_("Priority"), 'priority_name', 'text',
                                       true);
                    break;

                case 'owner':
                    $owner = &$this->addVariable(_("Owners"), 'user_id_owner',
                                                 'email', false, false, null,
                                                 array(false, true));
                    $owner->setDefault(_("Unassigned"));
                    break;

                case 'requester':
                    $this->addVariable(_("Requester"), 'user_id_requester',
                                       'email', false, false, null,
                                       array(false, true));
                    break;

                case 'created':
                    $this->addVariable(_("Created"), 'timestamp', 'date',
                                       false, false, null, $date_params);
                    break;

                case 'due':
                    $this->addVariable(_("Due"), 'due', 'datetime', false,
                                       false, null, $date_params);
                    break;

                case 'updated':
                    $this->addVariable(_("Updated"), 'date_updated', 'date',
                                       false, false, null, $date_params);
                    break;

                case 'assigned':
                    $this->addVariable(_("Assigned"), 'date_assigned', 'date',
                                       false, false, null, $date_params);
                    break;

                case 'resolved':
                    $this->addVariable(_("Resolved"), 'date_resolved', 'date',
                                       false, false, null, $date_params);
                    break;

                case 'attachments':
                    $this->addVariable(_("Attachments"), 'attachments', 'html',
                                       false);
                    break;

                default:
                    if (substr($field, 0, 10) == 'attribute_' &&
                        isset($attributes[substr($field, 10)])) {
                        $attribute = $attributes[substr($field, 10)];
                        if (!$attribute['params']) {
                            $attribute['params'] = array();
                        }
                        $var = &$this->addVariable(
                            $attribute['human_name'],
                            'attribute_' . $attribute['id'],
                            $attribute['type'], $attribute['required'],
                            $attribute['readonly'], $attribute['desc'],
                            $attribute['params']);
                        $var->setDefault($attribute['value']);
                    }
                    break;
                }
            }
        }
    }

}
