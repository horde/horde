<div data-role="page" id="nag-list">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#nag-lists', _("Lists")), 'logout' => true, 'title' => _("My Tasks"))) ?>

 <div data-role="content" class="ui-body">
  <ul data-role="listview"></ul>
 </div>

 <div data-role="footer" data-id="nag-footer" data-position="fixed">
  <div data-role="navbar">
   <ul>
    <li><a href="#" data-rel="dialog" data-transition="slideup" data-icon="plus"><?php echo _("New") ?></a></li>
   </ul>
  </div>
 </div>
</div>

