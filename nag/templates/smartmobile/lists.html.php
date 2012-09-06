<div id="lists" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Lists"))) ?>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" id="nag-tasklists">
   <li>foo</li>
  </ul>
 </div>

 <?php if ($this->create_form): ?>
 <div data-role="footer" data-id="nag-footer" data-position="fixed">
  <div data-role="navbar">
   <ul>
    <li><a href="#create" data-rel="dialog" data-transition="slideup" data-icon="plus"><?php echo _("New") ?></a></li>
   </ul>
  </div>
 </div>
<?php endif; ?>
</div>