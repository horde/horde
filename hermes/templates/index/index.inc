<!-- begin header -->
<div id="hermesHeader">
  <div id="hermesLogo"><h1><a href="#"><?php echo $registry->get('name') ?></a></h1></div>
  <!-- begin services -->
  <div id="hermesServices">
    <?php if (count($GLOBALS['conf']['menu']['apps'])): ?>
    <div id="hermesApplications">
      <ul>
        <?php foreach ($menu->getSiteLinks() as $key => $menu_item): ?>
        <?php if ($menu_item == 'separator'): ?>
      </ul>
      <span class="hermesSeparator">|</span>
      <ul>
        <?php else: ?>
        <li><a href="<?php echo $menu_item['url'] ?>" title="<?php echo $menu_item['text'] ?>" id="hermesMenu-<?php echo $key ?>"><img src="<?php echo $menu_item['icon_path'] . '/' . $menu_item['icon'] ?>" alt="<?php echo $menu_item['text'] ?>" /> <?php echo $menu_item['text'] ?></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach ($GLOBALS['conf']['menu']['apps'] as $app): ?>
        <?php if ($GLOBALS['registry']->hasAjaxView($app)): ?>
        <li><a href="<?php echo $GLOBALS['registry']->get('webroot', $app) ?>" id="hermesApplication-<?php echo $app ?>" title="<?php echo $GLOBALS['registry']->get('name', $app) ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php else: ?>
        <li><a href="#" onclick="HermesCore.go('app:<?php echo $app ?>')" id="hermesApplication-<?php echo $app ?>" title="<?php echo $GLOBALS['registry']->get('name', $app) ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <a href="#" id="hermesNotifications" class="hermesNotifications" title="<?php echo ("0 notifications") ?>"></a>
    <span class="hermesSeparator">|</span>
    <a href="#" id="hermesOptions"><?php echo _("Options") ?></a><?php if ($help_link) echo ' <span class="hermesSeparator">|</span> ' . $help_link; if (Horde_Menu::showService('logout')) { $ak = Horde::getAccessKey(_("_Logout")); echo ' <span class="hermesSeparator">|</span> <a href="#" accesskey="' . $ak . '" id="hermesLogout">' . Horde::highlightAccessKey(_("_Logout"), $ak) . '</a>'; } ?>
  </div>
  <!-- end services -->
 <!-- begin top bar -->
 <div id="hermesBar">
  <div id="hermesNav">
    <ul>
      <li><a href="#" id="hermesNavTime"><?php echo _("Log")?></a></li>
      <li><a href="#" id="hermesNavCostObjects"><?php echo _("Cost Objects")?></a></li>
      <li><a href="#" id="hermesNavClients"><?php echo _("Clients")?></a></li>
      <li><a href="#" id="hermesNavTypes"><?php echo _("Job Types")?></a></li>
      <li><a href="#" id="hermesNavSearch"><?php echo _("Search")?></a></li>
    </ul>
  </div>
 </div>
 <!-- end top bar -->
 
</div>
<!-- end header -->
<!-- begin main area -->
<div id="hermesMain">

<!-- begin left menu -->
<div id="hermesMenu">
    <div class="hermesMenuSummary">
      <h3 style="margin-top:46px;">Time Summary:</h3>
      <span class="hermesMenuSummaryToday">Today:</span> 10 hours<br />
      <span class="hermesMenuSummaryToday">Week:</span> 45 hours<br />
    </div>
    <br />
    <h3>Mini-Cal Here, with days containing hours worked highlighted</h3>
    <p> Dates will link to a search-by-date view for the selected date?</p>
    <br />
    <div id="hermesMenuBlocks">
      <a href="#" class="hermesAdd">+</a>
      <h3>Timers</h3>
      <span class="hermesTimerStop">x</span>
      <div class="hermesTimerOn rounded">
         Big Project Timer
         <div class="hermesTimerTime">2:32</div>
      </div>
      <span class="hermesTimerStop">x</span>
      <div class="hermesTimerOn rounded">
         Big Project Timer
         <div class="hermesTimerTime">2:32</div>
      </div>
        <div id="hermesMenuRecent">
          <h3>Recent Projects</h3>
          <div class="hermesMenuRecent rounded">
             Big Project:
             <div class="hermesTimerTime">20 Hours (30%)</div>
          </div>
          <div class="hermesMenuRecent rounded">
             Big Project Timer
             <div class="hermesTimerTime">30 HOurs (20%)</div>
          </div>
        </div>
    </div>    
</div>
<!-- end left menu -->

<!-- begin body -->
<div id="hermesBody">
<div id="hermesLoading" style="display:none"></div>
<?php
require dirname(__FILE__) . '/timeedit.inc';
?>
<div id="hermesViewIframe" style="display:none"></div>
</div>
<!-- end body -->

</div>
<!-- end main area -->
