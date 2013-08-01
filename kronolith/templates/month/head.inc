<table id="kronolithViewMonth" class="kronolith-view kronolithViewMonth" cellspacing="0">
<thead class="kronolith-view-head">
 <tr>
  <th class="kronolith-first-col">&nbsp;</th>
<?php if (!$prefs->getValue('week_start_monday')): ?>
  <th><?php echo _("Sunday") ?></th>
<?php endif; ?>
  <th><?php echo _("Monday") ?></th>
  <th><?php echo _("Tuesday") ?></th>
  <th><?php echo _("Wednesday") ?></th>
  <th><?php echo _("Thursday") ?></th>
  <th><?php echo _("Friday") ?></th>
  <th><?php echo _("Saturday") ?></th>
<?php if ($prefs->getValue('week_start_monday')): ?>
  <th><?php echo _("Sunday") ?></th>
<?php endif; ?>
 </tr>
</thead>
<tbody id="kronolith-month-body">
