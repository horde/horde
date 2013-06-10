<form id="wicked-diff" method="get" action="diff.php">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="<?php echo $this->h($this->name) ?>" />
<input type="hidden" id="wicked-diff-v2" name="v2" value="" />

<h1 class="header">
  <?php echo _("History") ?>:
  <?php echo $this->pageLink ?>
  <?php echo $this->refreshLink ?>
</h1>

<br />
<table width="100%" cellspacing="0" class="horde-table">
 <thead><tr>
  <th align="left" width="1%"><?php echo _("Version") ?></th>

<?php if ($this->remove): ?>
  <th align="center" class="nowrap" style="width:1%"><?php echo $this->remove ?></th>
<?php endif ?>
<?php if ($this->edit): ?>
  <th align="center" class="nowrap" style="width:1%"><?php echo $this->edit ?></th>

  <th align="center" class="nowrap" style="width:1%"><?php echo $this->restore ?></th>
<?php endif ?>

  <th align="left" style="width:10%"><?php echo _("Author") ?></th>
  <th align="left" style="width:30%"><?php echo _("Created") ?></th>
  <th align="center" class="nowrap" style="width:1%"><?php echo _("Diff From") ?></th>
  <th align="center" class="nowrap" style="width:1%"><?php echo _("Diff To") ?></th>
  <th align="left" style="width:50%"><?php echo _("Change Log") ?></th>
 </tr></thead>
 <tbody>
