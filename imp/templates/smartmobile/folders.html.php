<div id="folders" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Folders"))) ?>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" data-filter-placeholder="<?php echo _("Filter mailboxes...") ?>" id="imp-folders-list"></ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed" data-tap-toggle="false">
  <a href="#folders-refresh" data-icon="refresh"><?php echo _("Refresh") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#compose"><?php echo _("New Message") ?></a>
<?php endif ?>
  <a href="#folders-more" data-rel="popup"><?php echo _("More...") ?></a>
 </div>

 <div data-role="popup" data-history="false" data-theme="a" id="folders-more">
  <ul data-role="listview" data-inset="true">
   <li data-icon="gear">
    <a href="#folders-showall"><?php echo _("Show All") ?></a>
   </li>
   <li data-icon="gear" style="display:none">
    <a href="#folders-showpoll"><?php echo _("Show Polled") ?></a>
   </li>
   <li data-icon="refresh">
    <a href="#folders-rebuild"><?php echo _("Rebuild") ?></a>
   </li>
  </ul>
 </div>
</div>
