<div id="mailbox" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#folders', _("Folders")), 'logout' => true, 'title' => '&nbsp;')) ?>

 <div data-role="content">
  <ul id="imp-mailbox-list" data-role="listview"></ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed" data-tap-toggle="false">
  <a href="#mailbox-refresh" data-icon="refresh"><?php echo _("Refresh") ?></a>
<?php if ($this->canSearch): ?>
  <a href="#search" data-icon="search"><?php echo _("Search") ?></a>
<?php endif ?>
<?php if ($this->canCompose): ?>
  <a href="#compose"><?php echo _("New Message") ?></a>
<?php endif ?>
 </div>

 <div id="imp-mailbox-buttons" style="display:none">
  <a data-swipe="delete" data-role="button" data-inline="true" data-theme="a" href="#mailbox-delete"><?php echo _("Delete") ?></a>
  <a data-swipe="spam" data-role="button" data-inline="true" data-theme="a" href="#mailbox-spam"><?php echo _("Spam") ?></a>
  <a data-swipe="innocent" data-role="button" data-inline="true" data-theme="a" href="#mailbox-innocent"><?php echo _("Innocent") ?></a>
 </div>
</div>
