<div id="folders" data-role="page">
  <div data-role="header">
    <a href="<?php echo Horde::getServiceLink('portal', 'horde')?>" class="ui-btn-left"><?php echo _("Portal")?></a>
    <h1><?php echo _("Folders") ?></h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div data-role="content">
    <ul data-role="listview">
      <li><a href="#mailbox"><img src="themes/graphics/folders/inbox.png" class="ui-li-icon" />Inbox<span class="ui-li-count">99</span></a></li>
      <li><a href="#mailbox"><img src="themes/graphics/folders/folder.png" class="ui-li-icon" />Another Folder</a></li>
      <li><a href="#mailbox"><img src="themes/graphics/folders/folder.png" class="ui-li-icon" />&nbsp;&nbsp;Sub folder</a></li>
      <li><a href="#"><img src="themes/graphics/folders/trash.png" class="ui-li-icon" />Trash</a></li>
    </ul>
  </div>
</div>
