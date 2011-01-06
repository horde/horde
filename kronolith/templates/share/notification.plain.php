<?php printf(_("Invitation from %s to the calendar \"%s\""), $this->identity->getName(), $this->calendar) ?>


<?php if ($this->ownerChange): ?>
<?php printf(_("%s has assigned the ownership of the calendar \"%s\" to you."), $this->identity->getName(), $this->calendar) ?>
<?php else: ?>
<?php printf(_("%s wants to share the calendar \"%s\" with you to grant you access to all events in this calendar."), $this->identity->getName(), $this->calendar) ?>
<?php if ($this->subscribe): ?>
 <?php echo _("To subscribe to this calendar, you need to click the following link:") ?>


<?php echo $this->subscribe ?>
<?php endif ?>
<?php endif ?>
