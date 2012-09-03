<div id="message" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#', _("Mailbox")), 'logout' => true, 'title' => '&nbsp;')) ?>

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

 <div data-role="footer" data-position="fixed">
  <div data-role="navbar" data-iconpos="left">
   <ul>
    <li><a href="#" id="imp-message-delete" data-rel="dialog" data-icon="delete"><?php echo _("Delete") ?></a></li>
<?php if ($this->canCompose): ?>
    <li><a href="#" id="imp-message-reply" data-icon="back"><?php echo _("Reply") ?></a></li>
    <li><a href="#" id="imp-message-forward" data-icon="forward"><?php echo _("Forward") ?></a></li>
<?php endif; ?>
   </ul>
  </div>
 </div>

 <div class="ui-bar" data-role="footer">
<?php if ($this->canSpam): ?>
  <a href="#" id="imp-message-spam" data-rel="dialog" data-icon="alert"><?php echo _("Spam") ?></a>
<?php endif ?>
<?php if ($this->canInnocent): ?>
  <a href="#" id="imp-message-innocent" data-rel="dialog" data-icon="check" style="display:none"><?php echo _("Innocent") ?></a>
<?php endif ?>
<?php if ($this->allowFolders): ?>
  <a href="#" id="imp-message-copymove" data-rel="dialog" data-icon="plus"><?php echo _("Copy/Move") ?></a>
<?php endif; ?>
<?php if ($this->canCompose): ?>
  <a href="#" id="imp-message-redirect" data-icon="forward"><?php echo _("Redirect") ?></a>
<?php endif; ?>
 </div>

 <div class="ui-bar" data-role="footer">
  <a href="#" id="imp-message-prev" data-icon="arrow-l"><?php echo _("Previous") ?></a>
  <a href="#" id="imp-message-next" data-icon="arrow-r"><?php echo _("Next") ?></a>
 </div>
</div>
