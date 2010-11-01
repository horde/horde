<?php printf(_("Your daily agenda for %s"), $this->date) ?>


<?php foreach ($this->events as $event): ?>
<?php if ($event->isAllDay()): ?>
<?php echo str_pad(_("All day") . ':', $this->pad) . $event->title ?>
<?php else: ?>
<?php echo str_pad($event->start->format($this->timeformat) . ':', $this->pad) . $event->title ?>
<?php endif ?>

<?php endforeach ?>
