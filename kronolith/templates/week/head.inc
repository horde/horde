<table cellspacing="0" width="100%" class="striped">
<thead>
 <tr>
  <th class="control" width="1%" colspan="3">&nbsp;</th>

<?php $colwidth = round((100 - count($this->days) - 1) / count($this->days)); foreach ($this->days as $day): ?>
  <th class="control" width="1%">&nbsp;</th>
  <th class="nowrap <?php echo ($day->isToday() ? 'selected-control' : 'control') ?>" width="<?php echo $colwidth ?>%" colspan="<?php echo $day->totalspan ?>">
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
   <strong><?php echo $day->link()->link(array('class' => 'widget')) . $day->getTime('%A, %d.') ?></a></strong>
  </th>
<?php endforeach; ?>
 </tr>
