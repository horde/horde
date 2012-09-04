<div id="folders" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Folders"))) ?>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" id="imp-folders-list">
   <?php echo $this->tree ?>
  </ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="#" data-role="button" id="imp-folders-refresh" data-icon="refresh"><?php echo _("Refresh") ?></a>
  <a href="#" data-role="button" id="imp-folders-showall"><?php echo _("Show All") ?></a>
  <a href="#" data-role="button" id="imp-folders-showpoll" style="display:none"><?php echo _("Show Polled") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#compose" data-role="button"><?php echo _("New Message") ?></a>
<?php endif ?>
 </div>
</div>
