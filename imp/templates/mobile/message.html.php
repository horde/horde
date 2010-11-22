<div id="message" data-role="page">
  <div data-role="header">
    <h1 id="imp-message-title"></h1>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
    <?php endif ?>
  </div>
  <div class="ui-body ui-body-c">
    <strong id="imp-message-subject"></strong><br>
    <small id="imp-message-date"></small>
  </div>
  <div class="ui-body ui-body-c">
    <a href="#" style="float:right;margin:0" data-role="button" data-icon="arrow-d" data-iconpos="notext"><?php echo _("Show more") ?></a>
    <?php echo _("From:") ?> <span id="imp-message-from"></span>
  </div>
  <div id="imp-message-body" data-role="content"></div>
</div>
