<form method="post" name="filters" action="<?php echo Horde::url('filters.php') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="" />
<div class="header">
 <?php echo _("Existing Rules") . ' ' . Horde_Help::link('ingo', 'filters_rules') ?>
</div>

<table width="100%" cellspacing="0" class="striped">
<thead>
 <tr class="item">
  <th width="1%"><?php echo _("Edit") ?></th>
  <th class="leftAlign"><?php echo _("Rule") ?></th>
  <th width="1%"><?php echo _("Enabled") ?></th>
  <?php if ($edit_allowed): ?>
  <th colspan="3" width="1%"><?php echo _("Move") ?></th>
  <?php endif; ?>
 </tr>
</thead>
<tbody>
