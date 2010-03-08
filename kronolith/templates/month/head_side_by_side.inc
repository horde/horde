<h1 class="header" style="text-align:center">
<?php
$prev = $this->getMonth(-1);
$next = $this->getMonth(1);
echo $this->link(-1)->link(array('title' => _("Last Month"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Month\', \'' . $prev->dateString() . '\');')) . Horde::img('nav/left.png', '<') . '</a>';
echo htmlspecialchars($this->date->strftime('%B %Y'));
echo $this->link(1)->link(array('title' => _("Next Month"), 'class' => 'iconNav', 'onclick' => 'return ShowView(\'Month\', \'' . $next->dateString() . '\');')) . Horde::img('nav/right.png', '>') . '</a>';
?>
</h1>

<table class="monthgrid" cellspacing="0">
<thead>
 <tr class="item">
<?php
$dayofweek = 0;
$offset = $prefs->getValue('week_start_monday') ? 1 : 0;
for ($day = (1 - $this->_startday); $day < (1 - $this->_startday) + $this->_daysInView; $day++):
?>
  <th><?php $date_ob = new Horde_Date(array('month' => $this->month, 'mday' => $day + $offset, 'year' => $this->year)); echo $date_ob->strftime('%A') ?></th>
<?php $dayofweek++; if (($dayofweek % 7 == 0) && ($dayofweek != 0)): ?>
  <th>&nbsp;</th>
<?php endif; endfor; ?>
 </tr>
</thead>
<tbody>
