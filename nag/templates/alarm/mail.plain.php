<?php echo _("We would like to remind you of this due task.") ?>


<?php echo $this->task->name ?>


<?php echo _("Date:") ?> <?php echo $this->due->strftime($this->dateFormat) ?>

<?php echo _("Time:") ?> <?php echo $this->due->format($this->timeFormat) ?>


<?php echo $this->task->desc ?>
