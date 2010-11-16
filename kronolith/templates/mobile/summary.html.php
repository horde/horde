<div data-role="page" id="overview">
  <div data-role="header">
   <h1><?php echo _("Summary")?></h1>
   <a class="ui-btn-left" href="<?php echo $this->portal?>"><?php echo _("Home")?></a>
   <a rel="external" class="ui-btn-right" data-icon="delete" href="<?php echo $this->logout?>"><?php echo _("Logout")?></a>
   <div data-role="navbar" class="ui-bar-b">
    <ul>
     <li><a href="#dayview"><?php echo _("Day")?></a></li>
     <li><a href="#monthview"><?php echo _("Month")?></a></li>
     <li><a href="#" class="ui-btn-active"><?php echo _("Summary")?></a></li>
    </ul>
   </div>
  </div>
  <div data-role="content" class="ui-body"></div>
  <div data-role="footer"></div>
</div>