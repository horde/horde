<div id="mailbox" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#folders', _("Folders")), 'logout' => true, 'title' => '&nbsp;')) ?>

 <div data-role="content">
  <ul id="imp-mailbox-list" data-role="listview"></ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed" data-tap-toggle="false">
  <a href="#mailbox-refresh" data-icon="refresh"><?php echo _("Refresh") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#compose"><?php echo _("New Message") ?></a>
<?php endif ?>
<?php if ($this->canSearch || $this->canPurge): ?>
  <a href="#mailbox-more" data-rel="popup"><?php echo _("More...") ?></a>
<?php endif; ?>
 </div>

 <div data-role="popup" data-history="false" data-theme="a" id="mailbox-more">
  <ul data-role="listview" data-inset="true">
<?php if ($this->canSearch): ?>
   <li data-icon="search">
    <a id="imp-mailbox-search" href="#search" data-rel="dialog"><?php echo _("Search") ?></a>
   </li>
   <li data-icon="search">
    <a id="imp-mailbox-searchedit" href="#search" data-rel="dialog"><?php echo _("Edit Search") ?></a>
   </li>
<?php endif; ?>
<?php if ($this->canPurge): ?>
   <li data-icon="delete">
    <a href="#mailbox-purge"><?php echo _("Purge Deleted") ?></a>
   </li>
<?php endif; ?>
  </ul>
 </div>

 <div id="imp-mailbox-buttons" style="display:none">
  <a data-role="button" data-inline="true" data-theme="a" href="#mailbox-delete"><?php echo _("Delete") ?></a>
  <a data-role="button" data-inline="true" data-theme="a" href="#mailbox-spam"><?php echo _("Spam") ?></a>
  <a data-role="button" data-inline="true" data-theme="a" href="#mailbox-innocent"><?php echo _("Innocent") ?></a>
 </div>

 <div data-role="popup" data-history="false" data-theme="a" id="imp-mailbox-taphold">
  <ul data-role="listview" data-inset="true">
   <li data-icon="delete">
    <a href="#mailbox-delete"><?php echo _("Delete") ?></a>
   </li>
   <li data-icon="alert">
    <a href="#mailbox-spam"><?php echo _("Spam") ?></a>
   </li>
   <li data-icon="check">
    <a href="#mailbox-innocent"><?php echo _("Innocent") ?></a>
   </li>
  </ul>
 </div>
</div>
