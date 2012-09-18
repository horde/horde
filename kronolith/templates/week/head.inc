<table class="kronolith-view kronolithViewDay<?php if ($this->sidebyside) echo ' kronolith-sidebyside' ?>">
<thead class="kronolith-view-head">
 <tr>
<?php if (!$more_timeslots): ?>
  <th class="kronolith-first-col">&nbsp;</th>
<?php endif ?>

<?php foreach ($this->days as $i => $day): ?>
<?php if ($more_timeslots): ?>
  <th class="kronolith-first-col">&nbsp;</th>
<?php endif ?>
  <th colspan="<?php echo $day->totalspan ?>">
<?php
if (Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
    ($GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') === true ||
     $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())) {
    echo Horde::url('new.php')
        ->add(array('date' => $day->dateString(),
                    'url' => $this->link(0, true)))
        ->link(array('title' => _("Create a New Event"),
                     'class' => 'iconAdd'))
        . Horde::img('new_small.png', '+')
        . '</a>';
}
?>
   <?php echo $day->link()->link(array('class' => 'widget')) . $day->getTime('%A, %d.') ?></a>
  </th>
<?php endforeach; ?>
 </tr>
