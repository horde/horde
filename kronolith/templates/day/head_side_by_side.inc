<thead class="kronolith-view-head">
 <tr>
  <th>&nbsp;</th>
<?php if (!count($this->_currentCalendars)): ?>
  <th>&nbsp;</th>
<?php endif; ?>
<?php foreach ($this->_currentCalendars as $cid => $cal): ?>
  <th colspan="<?php echo $this->span[$cid] ?>">
   <?php echo htmlspecialchars($cal->get('name')) ?>
  </th>
<?php endforeach; ?>
 </tr>
</thead>
