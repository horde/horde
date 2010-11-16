<div data-role="page" id="dayview">
  <div data-role="header">
   <h1><?php echo _("Day")?></h1>
   <a class="ui-btn-left" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
   <a rel="external" class="ui-btn-right" data-icon="delete" href="<?php echo $this->logout?>"><?php echo _("Logout")?></a>
   <div data-role="navbar" class="ui-bar-b">
    <ul>
     <li><a href="#" class="ui-btn-active"><?php echo _("Day")?></a></li>
     <li><a href="#monthview"><?php echo _("Month")?></a></li>
     <li><a href="#overview"><?php echo _("Summary")?></a></li>
    </ul>
   </div>
   <div class="ui-bar-b kronolithDayHeader">
    <a href="#" class="kronolithPrevDay" data-icon="arrow-l" data-iconpos="notext"><?php echo _("Previous")?></a>
    <span class="kronolithDayDate"></span>
    <a href="#" data-icon="arrow-r" data-iconpos="notext" class="kronolithNextDay"><?php echo _("Next")?></a></div>
  </div>
  <div data-role="content"></div>
  <div data-role="footer" data-position="fixed">
  </div>
</div>