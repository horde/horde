<div id="mailbox" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#folders', _("Folders")), 'logout' => true, 'title' => '&nbsp;')) ?>
 <div id="imp-mailbox-navtop" data-role="header" style="display:none">
  <a href="#" id="imp-mailbox-prev" data-icon="arrow-l"><?php echo _("Previous") ?></a></li>
  <h3 id="imp-mailbox-navtext"></h3>
  <a href="#" id="imp-mailbox-next" data-icon="arrow-r"><?php echo _("Next") ?></a></li>
 </div>

 <div data-role="content">
  <ul id="imp-mailbox-list" data-role="listview"></ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
<?php if ($this->canSearch): ?>
  <a href="#search" id="imp-mailbox-search" data-icon="search"><?php echo _("Search") ?></a>
<?php endif ?>
<?php if ($this->canCompose): ?>
  <a href="#compose"><?php echo _("New Message") ?></a>
<?php endif ?>
 </div>
</div>
