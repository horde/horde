<div id="mailbox" data-role="page">
  <div data-role="header">
    <?php if (!$this->allowFolders): ?>
    <a href="<?php echo $this->portal ?>" class="ui-btn-left"><?php echo _("Portal")?></a>
    <?php endif ?>
    <h1><?php echo _("Inbox") ?></h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div data-role="content">
    <ul data-role="listview">
      <li>
         <h3><a href="#message">Subject 1</a></h3>
         <p class="ui-li-aside">99</p>
         <p>Sender</p>
      </li>
      <li><a href="#message">Another Folder</a></li>
      <li><a href="#message">Sub folder</a></li>
      <li><a href="#message">Trash</a></li>
    </ul>
  </div>
</div>
