<div data-role="page" id="nag-list">
 <?php echo $this->smartmobileHeader(array('backlink' => array('#nag-lists', _("Lists")), 'logout' => true, 'title' => _("My Tasks"))) ?>

 <div data-role="content" class="ui-body">
  <div id="nag-notasks" style="display:none;"><?php echo _("No tasks to display") ?></div>
  <ul data-role="listview"></ul>
 </div>

 <div data-role="footer" data-id="nag-footer" data-position="fixed">
  <div data-role="navbar">
   <ul>
    <li><a href="#nag-taskform-view" data-rel="dialog" data-transition="slideup" data-icon="plus"><?php echo _("New") ?></a></li>
   </ul>
  </div>
 </div>
</div>

