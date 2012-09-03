<div id="folders" data-role="page">
 <div data-role="header">
  <a data-ajax="false" href="<?php echo $this->portal ?>"><?php echo _("Applications")?></a>
  <h1><?php echo _("Folders") ?></h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" data-ajax="false" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" id="imp-folders-list">
   <?php echo $this->tree ?>
  </ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="" data-role="button" id="imp-folders-showall"><?php echo _("Show All") ?></a>
  <a href="" data-role="button" id="imp-folders-showpoll" style="display:none"><?php echo _("Show Polled") ?></a>
  <a href="" data-role="button" id="imp-folders-refresh" data-icon="refresh"><?php echo _("Refresh") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#compose" data-role="button" data-icon="plus"><?php echo _("New Message") ?></a>
<?php endif ?>
 </div>
</div>
