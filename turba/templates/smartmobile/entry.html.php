<div id="entry" data-role="page">
 <div data-role="header">
  <a href="#browse" data-icon="arrow-l" data-direction="reverse"><?php echo _("Browse") ?></a>
  <h1><?php echo _("View Entry") ?></h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <dl id="turba-entry-dl">
  <span id="turba-entry-name-block">
   <dt><?php echo _("Name") ?></dt>
   <dd id="turba-entry-name"></dd>
  </span>
  <span id="turba-entry-email-block">
   <dt><?php echo _("Email") ?></dt>
   <dd id="turba-entry-email"></dd>
  </span>
 </dl>

 <div id="turba-entry-buttonbar" data-role="footer" class="ui-bar" data-position="fixed">
  <a href="" id="turba-entry-top" data-role="button" data-icon="arrow-u"><?php echo _("Top") ?></a>
 </div>
</div>
