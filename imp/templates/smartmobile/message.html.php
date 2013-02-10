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
  <a href="#message-more" data-rel="popup"><?php echo _("More...") ?></a>
 </div>

 <div data-role="popup" data-history="false" data-theme="a" id="message-more">
  <ul data-role="listview" data-inset="true"></ul>
 </div>

 <ul id="imp-message-more-data" style="display:none">
<?php if ($this->canCompose): ?>
  <li data-icon="forward">
   <a href="#message-forward"><?php echo _("Forward") ?></a>
  </li>
  <li data-icon="forward">
   <a href="#message-redirect"><?php echo _("Redirect") ?></a>
  </li>
<?php endif; ?>
<?php if ($this->canSpam): ?>
  <li data-icon="alert">
   <a href="#" id="imp-message-spam"><?php echo _("Spam") ?></a>
  </li>
<?php endif ?>
<?php if ($this->canInnocent): ?>
  <li data-icon="check">
   <a href="#" id="imp-message-innocent"><?php echo _("Innocent") ?></a>
  </li>
<?php endif ?>
<?php if ($this->allowFolders): ?>
  <li data-icon="plus">
   <a href="#" id="imp-message-copymove" data-rel="dialog"><?php echo _("Copy/Move") ?></a>
  </li>
<?php endif; ?>
  <li data-icon="arrow-l">
   <a href="#message-prev" id="imp-message-prev"><?php echo _("Previous") ?></a>
  </li>
  <li data-icon="arrow-r">
   <a href="#message-next" id="imp-message-next"><?php echo _("Next") ?></a>
  </li>
 </ul>
</div>
