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
