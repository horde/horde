<?php echo $this->subject ?>

<?php if (strlen($this->task->desc)): ?>
<?php echo _("The following is a more detailed description of the task:") ?>


<?php echo $this->task->desc ?>

<?php endif; ?>
<?php echo _("Attached is an iCalendar file with more information about the task. If your mail client supports iTip requests you can use this file to easily update your local copy of the task.") ?>


<?php echo _("If your email client doesn't support iTip requests you can use one of the following links to accept or decline the event.") ?>


<?php echo _("To accept the event:") ?>

<?php //echo $this->linkAccept ?>


<?php //echo _("To accept the event tentatively:") ?>

<?php //echo $this->linkTentative ?>


<?php //echo _("To decline the event:") ?>

<?php //echo $this->linkDecline ?>

