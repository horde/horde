<div id="folders" data-role="page">
  <div data-role="header">
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <h1><?php echo _("Folders") ?></h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div data-role="content">
    <ul data-role="listview">
      <?php echo $this->tree ?>
    </ul>
  </div>
</div>
