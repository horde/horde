<div id="mailbox" data-role="page">
 <div data-role="header">
  <a href="#folders" data-icon="arrow-l" data-direction="reverse"><?php echo _("Folders") ?></a>
  <h1 id="imp-mailbox-header">&nbsp;</h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div id="imp-mailbox-navtop" class="ui-bar ui-bar-a center" style="display:none">
  <a href="" id="imp-mailbox-prev1" data-inline="true" data-iconpos="notext" data-role="button" data-icon="arrow-l">Previous</a>
  <h2 class="ui-title">&nbsp;</h2>
  <a href="" id="imp-mailbox-next1" data-inline="true" data-role="button" data-icon="arrow-r" data-iconpos="notext">Next</a>
 </div>

 <div data-role="content">
  <ul id="imp-mailbox-list" data-role="listview"></ul>
 </div>

 <div id="imp-mailbox-navbottom" class="ui-bar ui-bar-a center" style="display:none">
  <a href="" id="imp-mailbox-prev2" data-inline="true" data-iconpos="notext" data-role="button" data-icon="arrow-l">Previous</a>
  <h2 class="ui-title">&nbsp;</h2>
  <a href="" id="imp-mailbox-next2" data-inline="true" data-role="button" data-icon="arrow-r" data-iconpos="notext">Next</a>
 </div>

<?php if ($this->canCompose || $this->canSearch): ?>
 <div data-role="footer" class="ui-bar" data-position="fixed">
<?php if ($this->canCompose): ?>
  <a href="#compose" data-role="button" data-icon="plus"><?php echo _("New Message") ?></a>
<?php endif ?>
<?php if ($this->canSearch): ?>
  <a href="#search" data-role="button" data-icon="search"><?php echo _("Search") ?></a>
<?php endif ?>
 </div>
<?php endif; ?>
</div>
