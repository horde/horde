<?php printf(_("Your daily agenda for %s"), $this->date) ?>


<?php foreach ($this->events as $event): ?>
<?php if ($event->isAllDay()): ?>
<?php echo Horde_String::pad(_("All day") . ':', $this->pad) . $event->title ?>
<?php else: ?>
<?php echo Horde_String::pad($event->start->format($this->timeformat) . ':', $this->pad) . $event->title ?>
<?php endif ?>

<?php endforeach ?>
