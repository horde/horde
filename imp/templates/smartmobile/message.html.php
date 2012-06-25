<div id="message" data-role="page">
 <div data-role="header">
  <a href="#" id="imp-message-back" data-icon="arrow-l" data-direction="reverse"><?php echo _("Mailbox") ?></a>
  <h1 id="imp-message-title">&nbsp;</h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div id="imp-message-header-toggle">
  <div class="ui-body ui-body-c">
   <a href="#" data-role="button" data-icon="arrow-d" data-iconpos="notext"><?php echo _("Show more") ?></a>
   <span id="imp-message-subject"></span>
   <span id="imp-message-subject-from">(<?php echo _("from") ?> <span id="imp-message-from"></span>)</span><br>
   <span id="imp-message-date"></span>
  </div>

  <div class="ui-body ui-body-c" style="display:none">
   <a href="#" data-role="button" data-icon="arrow-u" data-iconpos="notext"><?php echo _("Show less") ?></a>
   <table id="imp-message-headers"><tbody></tbody></table>
  </div>
 </div>

 <div id="imp-message-body" data-role="content"></div>

 <div data-role="footer" class="ui-bar">
  <div data-role="controlgroup" data-type="horizontal">
   <a href="#" id="imp-message-prev" data-role="button" data-icon="arrow-l"><?php echo _("Previous") ?></a>
   <a href="#" id="imp-message-next" data-role="button" data-icon="arrow-r"><?php echo _("Next") ?></a>
  </div>
<?php if ($this->canCompose): ?>
  <div data-role="controlgroup" data-type="horizontal">
   <a href="#" id="imp-message-reply" data-role="button" data-icon="back"><?php echo _("Reply") ?></a>
   <a href="#" id="imp-message-forward" data-role="button" data-icon="forward"><?php echo _("Forward") ?></a>
   <a href="#" id="imp-message-redirect" data-role="button" data-icon="forward"><?php echo _("Redirect") ?></a>
   <a href="#" id="imp-message-resume" data-role="button" data-icon="plus"><?php echo _("Edit as New") ?></a>
  </div>
<?php endif ?>
  <div class="imp-message-spacer"></div>
  <div data-role="controlgroup" data-type="horizontal">
   <a href="#" id="imp-message-delete" data-role="button" data-rel="dialog" data-icon="delete"><?php echo _("Delete") ?></a>
<?php if ($this->allowFolders): ?>
   <a href="#" id="imp-message-copy" data-role="button" data-rel="dialog" data-icon="plus"><?php echo _("Copy") ?></a>
   <a href="#" id="imp-message-move" data-role="button" data-rel="dialog" data-icon="minus"><?php echo _("Move") ?></a>
<?php endif ?>
<?php if ($this->canInnocent): ?>
   <a href="#" id="imp-message-innocent" data-role="button" data-rel="dialog" data-icon="check" style="display:none"><?php echo _("Innocent") ?></a>
<?php endif ?>
<?php if ($this->canSpam): ?>
   <a href="#" id="imp-message-spam" data-role="button" data-rel="dialog" data-icon="alert"><?php echo _("Spam") ?></a>
<?php endif ?>
  </div>
 </div>
</div>
