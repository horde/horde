<table class="monthgrid nopadding" cellspacing="0">
<thead>
 <tr>
<?php if (!$prefs->getValue('week_start_monday')): ?>
  <th class="item"><?php echo _("Sunday") ?></th>
<?php endif; ?>
  <th class="item"><?php echo _("Monday") ?></th>
  <th class="item"><?php echo _("Tuesday") ?></th>
  <th class="item"><?php echo _("Wednesday") ?></th>
  <th class="item"><?php echo _("Thursday") ?></th>
  <th class="item"><?php echo _("Friday") ?></th>
  <th class="item"><?php echo _("Saturday") ?></th>
<?php if ($prefs->getValue('week_start_monday')): ?>
  <th class="item"><?php echo _("Sunday") ?></th>
<?php endif; ?>
 </tr>
</thead>
<tbody>
