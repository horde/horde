<?php
/**
 * This is the template for notification emails sent to remind at open tickets.
 *
 * If you want to customize this template, copy it to
 * "reminder_email.plain.local.php" first, and edit that version.
 *
 * If using non-ASCII characters in this file, it has to saved with UTF-8
 * encoding. The following variables are available:
 *
 * $this->date:       The current date.
 * $this->full_name:  The full name of the notification recipient.
 * $this->role:       The recipient role, always 'owner'.
 */
?>
<?php echo _("Here is a summary of your open tickets:") ?>

<?php foreach ($this->tickets as $ticket): ?>


<?php echo _("Ticket #") ?><?php echo $ticket['id'] ?>: <?php echo $ticket['summary'] ?>

<?php echo _("Opened:") ?> <?php echo strftime('%a %d %B', $ticket['timestamp']) ?><?php echo Horde_Form_Type_date::getAgo($ticket['timestamp']) ?>

<?php echo _("State:") ?> <?php echo $ticket['state_name'] ?>

<?php echo _("Link:") ?> <?php echo $ticket['link'] ?>
<?php endforeach ?>
