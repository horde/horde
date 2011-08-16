<?php
/**
 * This is the template for notification emails sent after updating an existing
 * ticket.
 *
 * If you want to customize this template, copy it to
 * "notify_email.plain.local.php" first, and edit that version.  You can also
 * specify queue-specific notification messages by creating seperate files with
 * the following naming convention: "notify_email.plain.X.php" will be used for
 * notification emails for queue "X".
 *
 * If using non-ASCII characters in this file, it has to saved with UTF-8
 * encoding. The following variables are available:
 *
 * $this->ticket_url: The link to the ticket page.
 * $this->table:      The ticket summary table.
 * $this->comment:    The user comment(s) to include if any.
 * $this->dont_reply: Whether telling users to not reply is enabled in
 *                    the configuration.
 * $this->date:       The current date.
 * $this->full_name:  The full name of the notification recipient.
 * $this->auth_name:  The full name of the user that updated the ticket.
 * $this->role:       The recipient role, one of 'always', 'listener',
 *                    'requester', 'queue', 'owner' in ascending importance
 *                    order.
 */
?>
<?php if ($this->dont_reply): ?>
<?php echo _("DO NOT REPLY TO THIS MESSAGE. THIS EMAIL ADDRESS IS NOT MONITORED.") ?>


<?php endif; ?>
<?php echo $this->ticket_url ?>

<?php echo $this->table ?>


<?php echo $this->comment ?>
