<div id="mailbox" data-role="page">
  <div data-role="header">
    <h1 id="imp-mailbox-header">&nbsp;</h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div data-role="content">
    <ul id="imp-mailbox-list" data-role="listview">
    </ul>
  </div>
</div>
