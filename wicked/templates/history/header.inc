<form name="diff" method="get" action="diff.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="<?php echo htmlspecialchars($this->pageName()) ?>" />
<input type="hidden" name="v2" value="" />

<h1 class="header">
 <?php printf(_("History: %s"), Horde::link($this->pageUrl()) . htmlspecialchars($this->pageName()) . '</a>');
       echo Horde::link($this->pageUrl('history.php')) . Horde::img('reload.png', _("Reload History")) ?></a>
</h1>

<table width="100%" cellspacing="0" class="linedRow">
 <tr class="item">
  <th align="left" width="1%"><?php echo _("Version") ?></th>

<?php if ($this->allows(Wicked::MODE_REMOVE)): ?>
  <th align="center" class="nowrap" style="width:1%"><?php echo Horde::img('delete.png', _("Delete Version")) ?></th>
<?php endif; if ($this->allows(Wicked::MODE_EDIT)): ?>
  <th align="center" class="nowrap" style="width:1%"><?php echo Horde::img('edit.png', _("Edit Version")) ?></th>

  <th align="center" class="nowrap" style="width:1%"><?php echo Horde::img('restore.png', _("Restore Version")) ?></th>
<?php endif; ?>

  <th align="left" style="width:10%"><?php echo _("Author") ?></th>
  <th align="left" style="width:30%"><?php echo _("Created") ?></th>
<?php if ($allow_diff): ?>
  <th align="center" class="nowrap" style="width:1%"><?php echo _("Diff From") ?></th>
  <th align="center" class="nowrap" style="width:1%"><?php echo _("Diff To") ?></th>
<?php endif; ?>
  <th align="left" style="width:50%"><?php echo _("Change Log") ?></th>
 </tr>
