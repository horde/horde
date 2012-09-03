<div data-role="page" id="dayview">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Day"))) ?>
 <div data-role="header">
  <div data-role="navbar" class="ui-bar-a">
   <ul>
    <li><a href="#" class="ui-btn-active"><?php echo _("Day")?></a></li>
    <li><a href="#monthview"><?php echo _("Month")?></a></li>
    <li><a href="#overview"><?php echo _("Summary")?></a></li>
   </ul>
  </div>
  <div class="ui-bar-a kronolithDayHeader">
   <a href="#" class="kronolithPrevDay" data-icon="arrow-l" data-iconpos="notext"><?php echo _("Previous")?></a>
   <span class="kronolithDayDate"></span>
   <a href="#" data-icon="arrow-r" data-iconpos="notext" class="kronolithNextDay"><?php echo _("Next")?></a>
  </div>
 </div>
 <div data-role="content" class="ui-body"></div>
</div>
