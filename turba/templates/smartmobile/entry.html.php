<div id="entry" data-role="page">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#browse', _("Browse")), 'logout' => true, 'title' => _("View Entry"))) ?>

 <dl id="turba-entry-dl">
  <span id="turba-entry-name-block">
   <dt><?php echo _("Name") ?></dt>
   <dd id="turba-entry-name"></dd>
  </span>
  <span id="turba-entry-email-block">
   <dt><?php echo _("Email") ?></dt>
   <dd id="turba-entry-email"></dd>
   <ul id="turba-entry-email-list" data-role="listview" data-inset="true"></ul>
  </span>
 </dl>
</div>
