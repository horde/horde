<?php
/**
 * Base class for Whups' storage backend.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @todo    Needs updating to include method stubs for all required methods, to
 *          indicate what methods need to be implemented by other backends.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */
class Whups_Driver
{
    /**
     * @var array
     */
    protected $_params;

    /**
     * Constructor
     *
     * @param array $params  Parameter array.
     *
     * @return Whups_Driver_Base
     */
    public function __construct(array $params)
    {
        $this->_params = $params;
    }

    /**
     * Set ticket attributes
     *
     * @param array $info           Attributes to set
     * @param Whups_Ticket $ticket  The ticket which attributes to set.
     */
    public function setAttributes(array $info, Whups_Ticket &$ticket)
    {
        $ticket_id = $ticket->getId();

        foreach ($info as $name => $value) {
            if (substr($name, 0, 10) == 'attribute_' &&
                $ticket->get($name) != $value) {
                $attribute_id = (int)substr($name, 10);
                $ticket->change($name, $value);
                $this->_setAttributeValue($ticket_id, $attribute_id, $value);
                $this->updateLog($ticket_id, $GLOBALS['registry']->getAuth(), array('attribute' => $attribute_id . ':' . $value));
            }
        }
    }

    /**
     * Fetch ticket history
     *
     * @param integer $ticket_id  The ticket to fetch history for.
     *
     * @return array
     */
    public function getHistory($ticket_id)
    {
        $rows = $this->_getHistory($ticket_id);
        $attributes = array();
        foreach ($rows as $row) {
            if ($row['log_type'] == 'attribute' &&
                strpos($row['log_value'], ':')) {
                $attributes[(int)$row['log_value']] = $row['attribute_name'];
            }
        }

        $history = array();
        foreach ($rows as $row) {
            $label = null;
            $value = $row['log_value'];
            $transaction = $row['transaction_id'];

            $history[$transaction]['timestamp'] = $row['timestamp'];
            $history[$transaction]['user_id'] = $row['user_id'];
            $history[$transaction]['ticket_id'] = $row['ticket_id'];

            switch ($row['log_type']) {
            case 'comment':
                $history[$transaction]['comment'] = $row['comment_text'];
                $history[$transaction]['changes'][] = array(
                    'type' => $row['log_type'],
                    'value' => $row['log_value'],
                    'comment' => $row['comment_text']);
                continue 2;

            case 'queue':
                $label = $row['queue_name'];
                break;

            case 'version':
                $label = $row['version_name'];
                break;

            case 'type':
                $label = $row['type_name'];
                break;

            case 'state':
                $label = $row['state_name'];
                break;

            case 'priority':
                $label = $row['priority_name'];
                break;

            case 'attribute':
                continue 2;

            case 'due':
                $label = $row['log_value_num'];
                break;

            default:
                if (strpos($row['log_type'], 'attribute_') === 0) {
                    $attribute = substr($row['log_type'], 10);
                    if (isset($attributes[$attribute])) {
                        $label = $attributes[$attribute];
                    } else {
                        $label = sprintf(_("Attribute %d"), $attribute);
                    }
                    $history[$transaction]['changes'][] = array(
                        'type' => 'attribute',
                        'value' => $value,
                        'label' => $label);
                    continue 2;
                }
                break;
            }

            $history[$transaction]['changes'][] = array(
                'type' => $row['log_type'],
                'value' => $value,
                'label' => $label);
        }

        return $history;
    }

    /**
     */
    function getQueue($queueId)
    {
        return $GLOBALS['registry']->call('tickets/getQueueDetails',
                                          array($queueId));
    }

    /**
     */
    function getQueues()
    {
        return $GLOBALS['registry']->call('tickets/listQueues');
    }

    /**
     */
    function getVersionInfo($queue)
    {
        return $GLOBALS['registry']->call('tickets/listVersions',
                                          array($queue));
    }

    /**
     * Returns a hash of versions suitable for select lists.
     */
    function getVersions($queue, $all = false)
    {
        if (empty($queue)) {
            return array();
        }

        $versioninfo = $this->getVersionInfo($queue);
        $versions = array();
        $old_versions = false;
        foreach ($versioninfo as $vinfo) {
            if (!$all && !$vinfo['active']) {
                $old_versions = $vinfo['id'];
                continue;
            }
            $versions[$vinfo['id']] = $vinfo['name'];
            if (!empty($vinfo['description'])) {
                $versions[$vinfo['id']] .= ': ' . $vinfo['description'];
            }
            if ($all && !$vinfo['active']) {
                $versions[$vinfo['id']] .= ' ' . _("(inactive)");
            }
        }

        if ($old_versions) {
            $versions[$old_versions] = _("Older? Please update first!");
        }

        return $versions;
    }

    /**
     */
    function getVersion($version)
    {
        return $GLOBALS['registry']->call('tickets/getVersionDetails',
                                          array($version));
    }

    /**
     */
    function getCategories()
    {
        return array('unconfirmed' => _("Unconfirmed"),
                     'new' => _("New"),
                     'assigned' => _("Assigned"),
                     'resolved' => _("Resolved"));
    }

    /**
     * Returns the attributes for a specific ticket type.
     *
     * This method will check if external attributes need to be fetched from
     * hooks or whether to use the standard ones defined within Whups.
     *
     * @params integer $type  The ticket type.
     *
     * @return array  List of attributes.
     */
    function getAttributesForType($type = null)
    {
        $attributes = $this->_getAttributesForType($type);
        foreach ($attributes as $id => $attribute) {
            $attributes[$id] = array(
                'human_name' => $attribute['attribute_name'],
                'type'       => $attribute['attribute_type'],
                'required'   => $attribute['attribute_required'],
                'readonly'   => false,
                'desc'       => $attribute['attribute_description'],
                'params'     => $attribute['attribute_params']);
        }
        return $attributes;
    }

    /**
     * Returns the attributes for a specific ticket.
     *
     * This method will check if external attributes need to be fetched from
     * hooks or whether to use the standard ones defined within Whups.
     *
     * @params integer $ticket_id  The ticket ID.
     *
     * @return array  List of attributes.
     */
    function getAllTicketAttributesWithNames($ticket_id)
    {
        $ta = $this->_getAllTicketAttributesWithNames($ticket_id);

        $attributes = array();
        foreach ($ta as $id => $attribute) {
            $attributes[$attribute['attribute_id']] = array(
                'id'         => $attribute['attribute_id'],
                'human_name' => $attribute['attribute_name'],
                'type'       => $attribute['attribute_type'],
                'required'   => $attribute['attribute_required'],
                'readonly'   => false,
                'desc'       => $attribute['attribute_description'],
                'params'     => $attribute['attribute_params'],
                'value'      => $attribute['attribute_value']);
        }
        return $attributes;
    }

    /**
     * Deletes a queue.
     *
     * Should be called by driver subclasses after successful removal from the
     * backend. Takes only care of cleaning up queue permissions.
     *
     * @param integer $queueId  The id of the queue being deleted.
     */
    function deleteQueue($queueId)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        try {
            $perm = $perms->getPermission("whups:queues:$queueId");
            return $perms->removePermission($perm, true);
        } catch (Horde_Perms_Exception $e) {}

        return true;
    }

    /**
     * Deletes a form reply.
     *
     * Should be called by driver subclasses after successful removal from the
     * backend. Takes only care of cleaning up reply permissions.
     *
     * @param integer $reply  The id of the form reply being deleted.
     */
    function deleteReply($reply)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        try {
            $perm = $perms->getPermission("whups:replies:$reply");
            return $perms->removePermission($perm, true);
        } catch (Horde_Perms_Exception $e) {}

        return true;
    }

    /**
     */
    function filterTicketsByState($tickets, $state_category = array())
    {
        /* Take a list of tickets and return only those of the specified
         * state_category. */
        $tickets_filtered = array();
        foreach ($tickets as $ticket) {
            foreach ($state_category as $state) {
                if ($ticket['state_category'] == $state) {
                    $tickets_filtered[] = $ticket;
                }
            }
        }

        return $tickets_filtered;
    }

    /**
     * Sends email notifications to a list of recipients.
     *
     * We do some ugly work in here to make sure that no one gets comments
     * mailed to them that they shouldn't see (because of group permissions).
     *
     * @param array $opts  Option hash with notification information.
     *                     Possible values:
     *                     - ticket:     (Whups_Ticket) A ticket. If not set,
     *                                   this is assumed to be a reminder
     *                                   message.
     *                     - recipients: (array|string) The list of recipients.
     *                     - subject:    (string) The email subject.
     *                     - message:    (string) The email message text.
     *                     - from:       (string) The email sender.
     *                     - new:        (boolean, optional) Whether the passed
     *                                   ticket was just created.
     */
    function mail(array $opts)
    {
        global $conf, $registry, $prefs;

        $opts = array_merge(array('ticket' => false, 'new' => false), $opts);

        /* Set up recipients and message headers. */
        if (!is_array($opts['recipients'])) {
            $opts['recipients'] = array($opts['recipients']);
        }

        $mail = new Horde_Mime_Mail(array(
            'X-Whups-Generated' => 1,
            'User-Agent' => 'Whups ' . $registry->getVersion(),
            'Precedence' => 'bulk',
            'Auto-Submitted' => $opts['ticket'] ? 'auto-replied' : 'auto-generated'));

        $mail_always = null;
        if ($opts['ticket'] && !empty($conf['mail']['always_copy'])) {
            $mail_always = $conf['mail']['always_copy'];
            if (strpos($mail_always, '<@>') !== false) {
                try {
                    $mail_always = str_replace('<@>', $opts['ticket']->get('queue_name'), $mail_always);
                } catch (Whups_Exception $e) {
                    $mail_always = null;
                }
            }
            if ($mail_always) {
                $opts['recipients'][] = $mail_always;
            }
        }

        if ($opts['ticket'] &&
            ($queue = $this->getQueue($opts['ticket']->get('queue'))) &&
             !empty($queue['email'])) {
            $mail->addHeader('From', $queue['email']);
        } elseif (!empty($conf['mail']['from_addr'])) {
            $mail->addHeader('From', $conf['mail']['from_addr']);
        } else {
            $mail->addHeader('From', Whups::formatUser($opts['from']));
        }

        if ($opts['ticket']) {
            $opts['subject'] = '[' . $registry->get('name') . ' #'
                . $opts['ticket']->getId() . '] ' . $opts['subject'];
        }
        $mail->addHeader('Subject', $opts['subject']);

        /* Get our array of comments, sorted in the appropriate order. */
        if ($opts['ticket']) {
            $comments = $this->getHistory($opts['ticket']->getId());
            if ($conf['mail']['commenthistory'] == 'new' && count($comments)) {
                $comments = array_pop($comments);
                $comments = array($comments);
            } elseif ($conf['mail']['commenthistory'] != 'chronological') {
                $comments = array_reverse($comments);
            }
        } else {
            $comments = array();
        }

        /* Don't notify any email address more than once. */
        $seen_email_addresses = array();

        /* Get VFS handle for attachments. */
        if ($opts['ticket']) {
            $vfs = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Vfs')
                ->create();
            $attachments = Whups::getAttachments($opts['ticket']->getId());
        }

        foreach ($opts['recipients'] as $user) {
            if ($user == $opts['from'] &&
                $user == $GLOBALS['registry']->getAuth() &&
                $prefs->getValue('email_others_only')) {
                continue;
            }

            /* Make sure to check permissions as a guest for the 'always_copy'
             * address, and as the recipient for all others. */
            $to = $full_name = '';
            if (!empty($mail_always) && $user == $mail_always) {
                $mycomments = Whups::permissionsFilter(
                    $comments, 'comment', Horde_Perms::READ, '');
                $to = $mail_always;
            } else {
                $details = Whups::getUserAttributes($user);
                if (!empty($details['email'])) {
                    $to = Whups::formatUser($details);
                    $mycomments = Whups::permissionsFilter(
                        $comments, 'comment', Horde_Perms::READ, $details['user']);
                }
                $full_name = $details['name'];
            }

            /* We may have no recipients due to users excluding themselves
             * from self notifies. */
            if (!$to) {
                continue;
            }

            /* Add attachments. */
            $attachmentAdded = false;
            if (empty($GLOBALS['conf']['mail']['link_attach']) &&
                $opts['ticket']) {
                /* We don't know how many attachments exactly have been added
                 * for the last recipient, but we need to remove all of them
                 * because the attachment list is potentially limited by
                 * permissions. */
                for ($i = 0, $c = count($attachments); $i < $c; $i++) {
                    $mail->removePart($i);
                }
                foreach ($mycomments as $comment) {
                    foreach ($comment['changes'] as $change) {
                        if ($change['type'] == 'attachment') {
                            foreach ($attachments as $attachment) {
                                if ($attachment['name'] == $change['value']) {
                                    if (!isset($attachment['part'])) {
                                        $attachment['part'] = new Horde_Mime_Part();
                                        $attachment['part']->setType(Horde_Mime_Magic::filenameToMime($change['value'], false));
                                        $attachment['part']->setDisposition('attachment');
                                        $attachment['part']->setContents($vfs->read(Whups::VFS_ATTACH_PATH . '/' . $opts['ticket']->getId(), $change['value']));
                                        $attachment['part']->setName($change['value']);
                                    }
                                    $mail->addMimePart($attachment['part']);
                                    $attachmentAdded = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $formattedComment = $this->formatComments($mycomments, $opts['ticket']->getId());
            if (!$attachmentAdded &&
                empty($formattedComment) &&
                $prefs->getValue('email_comments_only')) {
                continue;
            }

            try {
                $addr_arr = Horde_Mime_Address::parseAddressList($to);
                if (isset($addr_arr[0])) {
                    $bare_address = strtolower($addr_arr[0]['mailbox'] . '@' . $addr_arr[0]['host']);
                    if (!empty($seen_email_addresses[$bare_address])) {
                        continue;
                    }
                    $seen_email_addresses[$bare_address] = true;

                    if (empty($full_name) && isset($addr_arr[0]['personal'])) {
                        $full_name = $addr_arr[0]['personal'];
                    }
                }
            } catch (Horde_Mime_Exception $e) {}

            // use email address as fallback
            if (empty($full_name)) {
                $full_name = $to;
            }

            $body = str_replace(
                array('@@comment@@', '@@full_name@@'),
                array("\n\n" . $formattedComment, $full_name),
                $opts['message']);
            $mail->setBody($body);

            $mail->addHeader('Message-ID', Horde_Mime::generateMessageId());
            if ($opts['ticket']) {
                $message_id = '<whups-' . $opts['ticket']->getId() . '-'
                    . md5($user) . '@' . $conf['server']['name'] . '>';
                if ($opts['new']) {
                    $mail->addHeader('Message-ID', $message_id);
                } else {
                    $mail->addHeader('In-Reply-To', $message_id);
                    $mail->addHeader('References', $message_id);
                }
            }

            $mail->clearRecipients();
            $mail->addHeader('To', $to);

            try {
                $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'), true);
                $entry = sprintf('%s Message sent to %s from "%s"',
                                 $_SERVER['REMOTE_ADDR'], $to,
                                 $GLOBALS['registry']->getAuth());
                Horde::logMessage($entry, 'INFO');
            } catch (Horde_Mime_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }
    }

    /**
     * Converts a changeset array to a plain text comment snippet.
     *
     * @param array $comments  A changeset list.
     * @param integer $ticket  A ticket ID.
     *
     * @return string  The formatted comment text, if any.
     */
    function formatComments($comments, $ticket)
    {
        $text = '';
        foreach ($comments as $comment) {
            if (!empty($comment['comment_text'])) {
                $text .= "\n"
                    . sprintf(_("%s (%s) wrote:"),
                              Whups::formatUser($comment['user_id']),
                              strftime('%Y-%m-%d %H:%M', $comment['timestamp']))
                    . "\n\n" . $comment['comment_text'] . "\n\n\n";
            }

            /* Add attachment links. */
            if (empty($GLOBALS['conf']['mail']['link_attach'])) {
                continue;
            }
            foreach ($comment['changes'] as $change) {
                if ($change['type'] != 'attachment') {
                    continue;
                }
                $url_params = array('actionID' => 'download_file',
                                    'file' => $change['value'],
                                    'ticket' => $ticket);
                $text .= "\n"
                    . sprintf(_("%s (%s) uploaded: %s"),
                              Whups::formatUser($comment['user_id']),
                              strftime('%Y-%m-%d %H:%M', $comment['timestamp']),
                              $change['value'])
                    . "\n\n"
                    . Horde::url(Horde::downloadUrl($change['value'], $url_params), true)
                    . "\n\n\n";
            }
        }

        return $text;
    }

}
