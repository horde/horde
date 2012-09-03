<div id="message" data-role="page">
 <div data-role="header">
  <a href="#" id="imp-message-back" data-icon="arrow-l" data-direction="reverse"><?php echo _("Mailbox") ?></a>
  <h1 id="imp-message-title">&nbsp;</h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" data-ajax="false" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div data-role="collapsible" data-iconpos="right" data-theme="b" data-content-theme="d">
  <h4>
   <span id="imp-message-from"></span>
   <span id="imp-message-date"></span>
  </h4>
  <table id="imp-message-headers"><tbody></tbody></table>
 </div>

 <div id="imp-message-atc" data-role="collapsible" data-iconpos="right" data-content-theme="d">
  <h4><span id="imp-message-atclabel"></span></h4>
  <ul data-inset="true" data-role="listview" id="imp-message-atclist"></ul>
 </div>

 <div id="imp-message-body" data-role="content"></div>

 <div data-role="footer" class="ui-bar">
  <div data-role="controlgroup" data-type="horizontal">
   <a href="" id="imp-message-top" data-role="button" data-icon="arrow-u"><?php echo _("Top") ?></a>
   <a href="#" id="imp-message-prev" data-role="button" data-icon="arrow-l"><?php echo _("Previous") ?></a>
   <a href="#" id="imp-message-next" data-role="button" data-icon="arrow-r"><?php echo _("Next") ?></a>
  </div>
  <div class="imp-message-spacer"></div>
<?php if ($this->canCompose): ?>
  <div data-role="controlgroup" data-type="horizontal">
   <a href="#" id="imp-message-reply" data-role="button" data-icon="back"><?php echo _("Reply") ?></a>
   <a href="#" id="imp-message-forward" data-role="button" data-icon="forward"><?php echo _("Forward") ?></a>
   <a href="#" id="imp-message-redirect" data-role="button" data-icon="forward"><?php echo _("Redirect") ?></a>
  </div>
  <div class="imp-message-spacer"></div>
<?php endif ?>
  <div data-role="controlgroup" data-type="horizontal">
   <a href="#" id="imp-message-delete" data-role="button" data-rel="dialog" data-icon="delete"><?php echo _("Delete") ?></a>
<?php if ($this->allowFolders): ?>
   <a href="#" id="imp-message-copymove" data-role="button" data-rel="dialog" data-icon="plus"><?php echo _("Copy/Move") ?></a>
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
