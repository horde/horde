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
      <ul>
        <?php else: ?>
        <li><a href="<?php echo $menu_item['url'] ?>" title="<?php echo $menu_item['text'] ?>" id="hermesMenu-<?php echo $key ?>"><img src="<?php echo $menu_item['icon_path'] . '/' . $menu_item['icon'] ?>" alt="<?php echo $menu_item['text'] ?>" /> <?php echo $menu_item['text'] ?></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach ($GLOBALS['conf']['menu']['apps'] as $app): ?>
        <?php if ($app == 'horde'): ?>
          <li><a href="<?php echo $GLOBALS['registry']->getServiceLink('portal');?>" id="hermesApplication-<?php echo $app ?>" title="<?php echo $GLOBALS['registry']->get('name', $app) ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php elseif ($GLOBALS['registry']->hasView(Hore_Registry::VIEW_DYNAMIC, $app)): ?>
          <li><a href="<?php echo $GLOBALS['registry']->get('webroot', $app) ?>" id="hermesApplication-<?php echo $app ?>" title="<?php echo $GLOBALS['registry']->get('name', $app) ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php else: ?>
          <li><a href="#" onclick="HermesCore.go('app:<?php echo $app ?>')" id="hermesApplication-<?php echo $app ?>" title="<?php echo $GLOBALS['registry']->get('name', $app) ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <ul>
      <li><a href="#" id="hermesNotifications" class="hermesNotifications" title="<?php echo ("0 notifications") ?>"></a></li>
      <li><a href="#" id="hermesOptions"><?php echo _("Options") ?></a></li>
      <?php if ($help_link): ?>
      <li><?php echo $help_link ?></li>
      <?php endif ?>
      <?php if (Horde_Menu::showService('logout')): $ak = Horde::getAccessKey(_("_Logout")); ?>
      <li><a href="#" accesskey="<?php echo $ak ?>" id="hermesLogout"><?php echo Horde::highlightAccessKey(_("_Logout"), $ak) ?></a></li>
      <?php endif ?>
    </ul>  </div>
  <!-- end services -->
 <!-- begin top bar -->
 <div id="hermesBar">
  <div id="hermesLoading" style="display:none"></div>
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
    <div id="hermesMenuBlocks">
  <div id="hermesMinical" class="hermesMinical">
    <table>
    <caption>
      <a id="hermesMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
      <a id="hermesMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
      <span id="hermesMinicalDate"><?php echo $today->format('F Y') ?></span>
    </caption>

    <thead>
      <tr>
        <th class="hermesMinicalEmpty">&nbsp;</th>
        <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
        <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo Horde_String::substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
        <?php endfor; ?>
      </tr>
    </thead>

    <tbody><tr><td></td></tr></tbody>
    </table>
  </div>
    <div class="hermesMenuSummary">
      <h3><?php echo _("Time Summary") ?>:</h3>
        <div class="hermesMenuItem rounded">
          <h4><?php echo _("Today") ?></h4>
          <div id="hermesSummaryTodayBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Billable")?></div>
          <div id="hermesSummaryTodayNonBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Non-Billable")?></div>
        </div>
        <div class="hermesMenuItem rounded">
          <h4><?php echo _("Total") ?></h4>
          <div id="hermesSummaryTotalBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Billable")?></div>
          <div id="hermesSummaryTotalNonBillable" class="hermesSummaryItem"><span class="hermesHours"></span> <?php echo _("Non-Billable")?></div>
        </div>
    </div>
      <h3>
        <span>Timers</span>
        <a href="#" class="hermesAdd">+</a>
      </h3>
      <div id="hermesMenuTimers">
      </div>
      <div id="hermesMenuRecent">
        <h3>Recent Projects</h3>
        <!--<div class="hermesMenuRecent rounded">
           Big Project:
           <div class="hermesTimerTime">20 Hours (30%)</div>
        </div>
        <div class="hermesMenuRecent rounded">
           Big Project Timer
           <div class="hermesTimerTime">30 HOurs (20%)</div>
        </div>-->
      </div>
    </div>
</div>
<!-- end left menu -->

<?php require __DIR__ . '/timer.inc'; ?>

<!-- begin body -->
<div id="hermesBody">
<?php
require __DIR__ . '/timeedit.inc';
require __DIR__ . '/search.inc';
?>
</div>
<!-- end body -->

</div>
<!-- end main area -->
