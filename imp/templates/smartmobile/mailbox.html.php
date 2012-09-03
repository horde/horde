<div id="mailbox" data-role="page">
 <div data-role="header">
  <a href="#folders" data-icon="arrow-l" data-direction="reverse"><?php echo _("Folders") ?></a>
  <h1 id="imp-mailbox-header">&nbsp;</h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" data-ajax="false" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div id="imp-mailbox-navtop" class="ui-bar ui-bar-a center" style="display:none">
  <a href="" id="imp-mailbox-prev" data-inline="true" data-iconpos="notext" data-role="button" data-icon="arrow-l">Previous</a>
  <h2 class="ui-title">&nbsp;</h2>
  <a href="" id="imp-mailbox-next" data-inline="true" data-role="button" data-icon="arrow-r" data-iconpos="notext">Next</a>
 </div>

 <div data-role="content">
  <ul id="imp-mailbox-list" data-role="listview"></ul>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="" id="imp-mailbox-top" data-role="button" data-icon="arrow-u"><?php echo _("Top") ?></a>
<?php if ($this->canCompose): ?>
  <a href="#compose" data-role="button" data-icon="plus"><?php echo _("New Message") ?></a>
<?php endif ?>
<?php if ($this->canSearch): ?>
  <a href="#search" id="imp-mailbox-search" data-role="button" data-icon="search"><?php echo _("Search") ?></a>
<?php endif ?>
 </div>
</div>
