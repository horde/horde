<?php echo _("We would like to remind you of this upcoming event.") ?>


<?php echo $this->event->getTitle($this->user) ?>


<?php echo _("Location:") ?> <?php echo $this->event->getLocation($this->user) ?>


<?php echo _("Date:") ?> <?php echo $this->event->start->strftime($this->dateFormat) ?>

<?php echo _("Time:") ?> <?php echo $this->event->start->format($this->timeFormat) ?>


<?php if (!$this->event->isPrivate($this->user)) echo $this->event->description ?>
