<?php
/**
 * This is the template for notification emails sent after deleting a ticket.
 *
 * If you want to customize this template, copy it to
 * "delete_email.plain.local.php" first, and edit that version.
 *
 * If using non-ASCII characters in this file, it has to saved with UTF-8
 * encoding. The following variables are available:
 *
 * $this->date:       The current date.
 * $this->full_name:  The full name of the notification recipient.
 * $this->auth_name:  The full name of the user that updated the ticket.
 * $this->role:       The recipient role, one of 'always', 'listener',
 *                    'requester', 'queue', 'owner' in ascending importance
 *                    order.
 */
?>
<?php printf(_("%s deleted this ticket."), $this->auth_name) ?>