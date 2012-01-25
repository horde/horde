<div id="folders" data-role="page">
  <div data-role="header">
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <h1><?php echo _("Folders") ?></h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div data-role="content">
    <ul data-role="listview" data-filter="true">
      <?php echo $this->tree ?>
    </ul>
  </div>
  <div data-role="footer" class="ui-bar" data-position="fixed">
    <?php if ($this->canCompose): ?>
    <a href="#compose" data-role="button" data-icon="plus"><?php echo _("New Message") ?></a>
    <?php endif ?>
    <?php if ($this->canSearch): ?>
    <a href="#search" data-role="button" data-icon="search"><?php echo _("Search") ?></a>
    <?php endif ?>
  </div>
</div>
