<?php echo $this->subject ?> (<? printf(_("on %s at %s"), $this->event->start->strftime('%x'), $this->event->start->strftime('%X')) ?>)

<? if (strlen($this->event->location)): ?>
<?php echo _("Location:") ?> <?php echo $this->event->location ?>


<? endif; ?>
<? if ($this->attendees): ?>
<?php echo _("Attendees:") ?> <?php echo implode(', ', $this->attendees) ?>


<? endif; ?>
<? if (strlen($this->event->description)): ?>
<?php echo _("The following is a more detailed description of the event:") ?>


<?php echo $this->event->description ?>


<? endif; ?>
<?php echo _("Attached is an iCalendar file with more information about the event. If your mail client supports iTip requests you can use this file to easily update your local copy of the event.") ?>


<?php echo _("If your email client doesn't support iTip requests you can use one of the following links to accept or decline the event.") ?>


<?php echo _("To accept the event:") ?>

<?php echo $this->linkAccept ?>


<?php echo _("To accept the event tentatively:") ?>

<?php echo $this->linkTentative ?>


<?php echo _("To decline the event:") ?>

<?php echo $this->linkDecline ?>

