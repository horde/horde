<table id="kronolithViewMonth" class="kronolith-view kronolithViewMonth kronolith-sidebyside">
<thead class="kronolith-view-head">
 <tr>
<?php
$dayofweek = 0;
$offset = $prefs->getValue('week_start_monday') ? 1 : 0;
for ($day = (1 - $this->_startday); $day < (1 - $this->_startday) + $this->_daysInView; $day++):
?>
<?php if ($dayofweek % 7 == 0): ?>
  <th>&nbsp;</th>
<?php endif ?>
  <th><?php $date_ob = new Horde_Date(array('month' => $this->month, 'mday' => $day + $offset, 'year' => $this->year)); echo $date_ob->strftime('%A') ?></th>
<?php $dayofweek++; ?>
<?php endfor ?>
 </tr>
</thead>
<tbody id="kronolith-month-body">
