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

 <div class="ui-bar" data-role="footer" data-position="fixed">
  <a href="#" id="imp-message-delete" data-rel="dialog" data-icon="delete"><?php echo _("Delete") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#" id="imp-message-reply" data-icon="back"><?php echo _("Reply") ?></a>
  <a href="#" id="imp-message-forward" data-icon="forward"><?php echo _("Forward") ?></a>
<?php endif; ?>
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

 <ul id="imp-message-pagination" data-role="pagination">
  <li class="ui-pagination-prev"><a href="#"><?php echo _("Previous") ?></a></li>
  <li class="ui-pagination-next"><a href="#"><?php echo _("Next") ?></a></li>
 </ul>
</div>
