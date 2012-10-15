<div id="message" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#', _("Mailbox")), 'logout' => true, 'title' => '&nbsp;')) ?>

 <div data-role="content">
  <div id="imp-message-headers" data-role="collapsible" data-iconpos="right" data-theme="b" data-content-theme="d">
   <h4>
    <span id="imp-message-from"></span>
    <span id="imp-message-date"></span>
   </h4>
   <table id="imp-message-headers-full"><tbody></tbody></table>
  </div>

  <div id="imp-message-atc" data-role="collapsible" data-iconpos="right" data-content-theme="d">
   <h4><span id="imp-message-atclabel"></span></h4>
   <ul data-inset="true" data-role="listview" id="imp-message-atclist"></ul>
  </div>

  <div id="imp-message-body"></div>
 </div>

 <div class="ui-bar" data-role="footer" data-position="fixed">
  <a href="#message-delete" id="imp-message-delete" data-icon="delete"><?php echo _("Delete") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#message-reply" data-icon="back"><?php echo _("Reply") ?></a>
<?php endif; ?>
  <a href="#message-more" id="imp-message-more"><?php echo _("More...") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#message-forward" data-more="true" data-icon="forward"><?php echo _("Forward") ?></a>
  <a href="#message-redirect" data-more="true" data-icon="forward"><?php echo _("Redirect") ?></a>
<?php endif; ?>
<?php if ($this->canSpam): ?>
  <a href="#" data-more="true" id="imp-message-spam" data-rel="dialog" data-icon="alert"><?php echo _("Spam") ?></a>
<?php endif ?>
<?php if ($this->canInnocent): ?>
  <a href="#" data-more="true" id="imp-message-innocent" data-rel="dialog" data-icon="check"><?php echo _("Innocent") ?></a>
<?php endif ?>
<?php if ($this->allowFolders): ?>
  <a href="#" data-more="true" id="imp-message-copymove" data-rel="dialog" data-icon="plus"><?php echo _("Copy/Move") ?></a>
<?php endif; ?>
  <a href="#message-prev" data-more="true" id="imp-message-prev" data-icon="arrow-l"><?php echo _("Previous") ?></a>
  <a href="#message-next" data-more="true" id="imp-message-next" data-icon="arrow-r"><?php echo _("Next") ?></a>
 </div>
</div>
