<div data-role="page" id="overview">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Summary"))) ?>

 <div data-role="header">
  <div data-role="navbar">
   <ul>
    <li><a href="#dayview"><?php echo _("Day")?></a></li>
    <li><a href="#monthview"><?php echo _("Month")?></a></li>
    <li><a href="#" class="ui-btn-active ui-state-persist"><?php echo _("Agenda")?></a></li>
   </ul>
  </div>
 </div>

 <div data-role="content" class="ui-body"></div>
</div>
