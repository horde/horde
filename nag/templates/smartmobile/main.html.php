<div data-role="page" id="list-view">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#lists', _("Lists")), 'logout' => true, 'title' => _("My Tasks"))) ?>
 <div data-role="content" class="ui-body">
 </div>
 <div data-role="footer" data-id="nag-footer" data-position="fixed">
  <div data-role="navbar">
   <ul>
    <li><a href="#create" data-rel="dialog" data-transition="slideup" data-icon="plus"><?php echo _("New") ?></a></li>
   </ul>
  </div>
 </div>
</div>
