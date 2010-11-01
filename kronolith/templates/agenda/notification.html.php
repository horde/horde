<h3><?php echo $this->h(sprintf(_("Your daily agenda for %s"), $this->date)) ?></h3>

<table style="border-collapse:collapse;border:1px solid #000" border="1">
  <?php foreach ($this->events as $event): ?>

  <tr>
    <td>
      <?php if ($event->isAllDay()): ?>
      <?php echo $this->h(_("All day")) ?>
      <?php else: ?>
      <?php echo $this->h($event->start->format($this->timeformat)) ?>
      <?php endif ?>

    </td>
    <td><a href="<?php echo $event->getViewUrl(array(), true) ?>"><?php echo $this->h($event->title) ?></a></td>
  </tr>
  <?php endforeach ?>

</table>
