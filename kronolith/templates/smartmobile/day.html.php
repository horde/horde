<div data-role="page" id="dayview">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Day"))) ?>

 <div data-role="header">
  <div data-role="navbar">
   <ul>
    <li><a href="#" class="ui-btn-active ui-state-persist"><?php echo _("Day")?></a></li>
    <li><a href="#monthview"><?php echo _("Month")?></a></li>
    <li><a href="#overview"><?php echo _("Agenda")?></a></li>
   </ul>
  </div>
 </div>

 <div data-role="header">
  <a href="#prevday" data-icon="arrow-l" data-iconpos="notext"><?php echo _("Previous")?></a>
  <h3 id="kronolithDayDate"></h3>
  <a href="#nextday" data-icon="arrow-r" data-iconpos="notext"><?php echo _("Next")?></a>
 </div>

 <div data-role="content" class="ui-body"></div>
</div>
