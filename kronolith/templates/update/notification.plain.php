<?php echo $this->header ?> (<?php printf(_("on %s at %s"), $this->event->start->strftime('%x'), $this->event->start->strftime('%X')) ?>)


<?php echo _("Calendar:") ?> <?php echo $this->calendar ?>


<?php if (strlen($this->event->location)): ?>
<?php echo _("Location:") ?> <?php echo $this->event->location ?>


<?php endif; ?>
<?php if (count($this->event->attendees)): ?>
<?php echo _("Attendees:") ?> <?php echo $this->event->attendees ?>


<?php endif; ?>
<?php if (strlen($this->event->description)): ?>
<?php echo _("The following is a more detailed description of the event:") ?>


<?php echo $this->event->description ?>


<?php endif; ?>
