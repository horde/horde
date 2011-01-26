<h1 class="header" style="text-align:center">
<?php
$currentView = $this instanceof Kronolith_View_WorkWeek ? 'WorkWeek' : 'Week';
$prev = $this->getWeek(-1);
$next = $this->getWeek(1);
echo $this->link(-1)->link(array('title' => _("Last week"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'' . $currentView . '\', \'' . $prev->dateString() . '\');')) . Horde::img('nav/left.png', '<') . '</a>';
echo '<span id="view_title">' . htmlspecialchars($this->days[$this->startDay]->getTime($prefs->getValue('date_format')) . ' - ' . $this->days[$this->endDay]->getTime($prefs->getValue('date_format'))) . '</span>';
echo $this->link(1)->link(array('title' => _("Next week"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'' . $currentView . '\', \'' . $next->dateString() . '\');')) . Horde::img('nav/right.png', '>') . '</a>';
?>
</h1>

<table cellspacing="0" width="100%" class="striped">
<thead>
 <tr>
  <th class="control" width="1%" colspan="3">&nbsp;</th>

<?php $colwidth = round((100 - count($this->days) - 1) / count($this->days)); foreach ($this->days as $day): ?>
  <th class="control" width="1%">&nbsp;</th>
  <th class="nowrap <?php echo ($day->isToday() ? 'selected-control' : 'control') ?>" width="<?php echo $colwidth ?>%" colspan="<?php echo $day->totalspan ?>">
<?php
if (Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
    ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
     $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents())) {
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
