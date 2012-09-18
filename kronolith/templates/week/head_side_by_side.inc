 <tr>
  <th>&nbsp;</th>
<?php foreach ($this->days as $day): ?>
<?php if (!count($this->_currentCalendars)): ?>
  <th>&nbsp;</th>
<?php endif ?>
<?php foreach ($this->_currentCalendars as $cid => $cal): ?>
  <th colspan="<?php echo $day->span[$cid] ?>">
   <?php echo htmlspecialchars($cal->get('name')) ?>
  </th>
<?php endforeach ?>
<?php endforeach ?>
 </tr>
