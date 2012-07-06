<!-- begin header -->
<div id="kronolithHeader">
  <div id="kronolithLogo"><h1><a href="#"><?php echo $registry->get('name') ?></a></h1></div>

  <!-- begin services -->
  <div id="kronolithServices">
    <?php if (count($GLOBALS['conf']['menu']['apps'])): ?>
    <div id="kronolithApplications">
      <ul>
        <?php foreach ($menu->getSiteLinks() as $key => $menu_item): ?>
        <?php if ($menu_item == 'separator'): ?>
      </ul>
      <ul>
        <?php else: ?>
        <li><a href="<?php echo $menu_item['url'] ?>" title="<?php echo $menu_item['text'] ?>" id="kronolithMenu-<?php echo $key ?>"><img src="<?php echo $menu_item['icon_path'] . '/' . $menu_item['icon'] ?>" alt="<?php echo $menu_item['text'] ?>" /> <?php echo $menu_item['text'] ?></a></li>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach ($GLOBALS['conf']['menu']['apps'] as $app): ?>
        <?php if ($GLOBALS['registry']->hasPermission($app)): ?>
        <?php  if ($app == 'horde'): ?>
          <li><a href="<?php echo $GLOBALS['registry']->getServiceLink('portal');?>" id="kronolithApplication-<?php echo $app ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php elseif ($GLOBALS['registry']->hasView(Horde_Registry::VIEW_DYNAMIC, $app)): ?>
        <li><a href="<?php echo $GLOBALS['registry']->get('webroot', $app) ?>" id="kronolithApplication-<?php echo $app ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php else: ?>
        <li><a href="#" onclick="KronolithCore.go('app:<?php echo $app ?>')" id="kronolithApplication-<?php echo $app ?>"><img src="<?php echo $GLOBALS['registry']->get('icon', $app) ?>" /> <?php echo $GLOBALS['registry']->get('name', $app) ?></a></li>
        <?php endif; ?>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <ul>
      <li><a href="#" id="kronolithNotifications" class="kronolithNotifications" title="<?php echo ("0 notifications") ?>"></a></li>
      <li><a href="#" id="kronolithOptions"><?php echo _("Options") ?></a></li>
      <?php if ($help_link): ?>
      <li><?php echo $help_link ?></li>
      <?php endif ?>
      <?php if (Horde_Menu::showService('logout')): $ak = Horde::getAccessKey(_("_Logout")); ?>
      <li><a href="#" accesskey="<?php echo $ak ?>" id="kronolithLogout"><?php echo Horde::highlightAccessKey(_("_Logout"), $ak) ?></a></li>
      <?php endif ?>
    </ul>
  </div>
  <!-- end services -->

<!-- begin top bar -->
<div id="kronolithBar">
  <div class="kronolithDateChoice" <?php $todayAccessKey = Horde::getAccessKey(_("_Today")); echo Horde::getAccessKeyAndTitle(_("_Today"), true) ?>>
    <div><?php echo $today->strftime($prefs->getValue('date_format_mini')) ?></div>
  </div>
  <div id="kronolithNav">
    <ul>
      <li><a id="kronolithNavDay" accesskey="1"><span><?php echo _("Day") ?></span></a></li>
      <li><a id="kronolithNavWorkweek" accesskey="2"><span><?php echo _("Work Week") ?></span></a></li>
      <li><a id="kronolithNavWeek" accesskey="3"><span><?php echo _("Week") ?></span></a></li>
      <li><a id="kronolithNavMonth" accesskey="4"><span><?php echo _("Month") ?></span></a></li>
      <li><a id="kronolithNavYear" accesskey="5"><span><?php echo _("Year") ?></span></a></li>
    <?php if (Kronolith::hasApiPermission('tasks')): ?>
      <li><a id="kronolithNavTasks" accesskey="6"><span><?php echo _("Tasks") ?></span></a></li>
    <?php endif; ?>
      <li><a id="kronolithNavAgenda" accesskey="<?php echo Kronolith::hasApiPermission('tasks') ? 7 : 6 ?>"><span><?php echo _("Agenda") ?></span></a></li>
    </ul>
  </div>
</div>
<!-- end top bar -->

</div>
<!-- end header -->

<!-- begin main area -->
<div id="kronolithMain">

<!-- begin left menu -->
<div id="kronolithMenu">
  <div id="kronolithAddEvents">
    <span id="kronolithNewEvent" accesskey="<?php echo ($ak = Horde::getAccessKey(_("_New event"))) ?>"><?php echo Horde::highlightAccessKey(_("_New event"), $ak) ?></span>
    <span id="kronolithQuickEvent" <?php echo Horde::getAccessKeyAndTitle(_("Quick _insert")) ?>>+</span>
  </div>

  <div id="kronolithMinical" class="kronolithMinical">
    <table>
    <caption>
      <a id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
      <a id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
      <span id="kronolithMinicalDate"><?php echo $today->format('F Y') ?></span>
    </caption>

    <thead>
      <tr>
        <th class="kronolithMinicalEmpty">&nbsp;</th>
        <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
        <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo Horde_String::substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
        <?php endfor; ?>
      </tr>
    </thead>

    <tbody><tr><td></td></tr></tbody>
    </table>
  </div>

  <form id="kronolithSearchForm" action="">
    <input type="text" id="kronolithSearchTerm" value="<?php echo _("Search") ?>" default="<?php echo _("Search") ?>" />
    <input id="kronolithSearchButton" type="button" value="<?php echo _("Go") ?>" />
  </form>

  <div id="kronolithLoadingCalendars">
    <?php echo _("Loading calendars...") ?>
  </div>

  <div id="kronolithMenuCalendars" style="display:none">
  <h3 id="kronolithCalendarsFirst">
    <?php if ($GLOBALS['registry']->getAuth() && !$prefs->isLocked('default_share')): ?>
    <a href="#" id="kronolithAddinternal" class="kronolithAdd" title="<?php echo _("New Calendar") ?>">+</a>
    <?php endif; ?>
    <span class="kronolithToggleCollapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Calendars") ?></span>
  </h3>

  <div id="kronolithMyCalendars" class="kronolithCalendars"></div>

  <?php if (Kronolith::hasApiPermission('tasks')): ?>
  <h3>
    <?php if ($GLOBALS['registry']->getAuth() && !$prefs->isLocked('default_share')): ?>
    <a href="#" id="kronolithAddtasklists" class="kronolithAdd" title="<?php echo _("New Task List") ?>">+</a>
    <?php endif; ?>
    <span class="kronolithToggleCollapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Task Lists") ?></span>
  </h3>

  <div id="kronolithMyTasklists" class="kronolithCalendars"></div>
  <?php endif; ?>

  <h3>
    <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddinternalshared" class="kronolithAdd">+</a>-->
    <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Calendars") ?></span>
  </h3>

  <div style="display:none">
    <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
    <div id="kronolithSharedCalendars" class="kronolithCalendars"></div>
  </div>

  <?php if (Kronolith::hasApiPermission('tasks')): ?>
  <h3>
    <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddtasklistsshared" class="kronolithAdd">+</a>-->
    <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Task Lists") ?></span>
  </h3>

  <div style="display:none">
    <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
    <div id="kronolithSharedTasklists" class="kronolithCalendars"></div>
  </div>
  <?php endif ?>

  <h3>
    <?php if ($registry->isAdmin()): ?>
    <a href="#" id="kronolithAddresource" class="kronolithAdd" title="<?php echo _("Add Resource") ?>">+</a>
    <?php endif; ?>
    <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Resources") ?></span>
  </h3>
  <div id="kronolithResourceCalendars" class="kronolithCalendars" style="display:none"></div>

  <div id="kronolithExternalCalendars"></div>

  <h3>
    <a href="#" id="kronolithAddremote" class="kronolithAdd" title="<?php echo _("Add Remote Calendar") ?>">+</a>
    <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Remote Calendars") ?></span>
  </h3>

  <div style="display:none">
    <div class="kronolithDialogInfo"><?php echo _("No items to display") ?></div>
    <div id="kronolithRemoteCalendars" class="kronolithCalendars"></div>
  </div>

  <h3>
    <a href="#" id="kronolithAddholiday" class="kronolithAdd" title="<?php echo _("Add Holidays") ?>">+</a>
    <span class="kronolithToggleExpand" title="<?php echo _("Expand") ?>"><?php echo _("Holidays") ?></span>
  </h3>

  <div id="kronolithHolidayCalendars" class="kronolithCalendars" style="display:none"></div>

  </div>
</div>
<!-- end left menu -->

<!-- begin edit form -->
<?php require __DIR__ . '/edit.inc'; ?>
<!-- end edit form -->

<!-- begin body -->
<div id="kronolithBody">
<div id="kronolithLoading" style="display:none"></div>
<?php
require __DIR__ . '/month.inc';
require __DIR__ . '/workweek.inc';
require __DIR__ . '/week.inc';
require __DIR__ . '/day.inc';
require __DIR__ . '/year.inc';
if (Kronolith::hasApiPermission('tasks')) {
    require __DIR__ . '/tasks.inc';
    require __DIR__ . '/task.inc';
}
require __DIR__ . '/agenda.inc';
?>
</div>
<!-- end body -->

</div>
<!-- end main area -->

<div id="kronolithQuickinsert" style="display:none">
  <form id="kronolithQuickinsertForm" action="">
    <input type="text" id="kronolithQuickinsertQ" size="35" value="" class="kronolithLongField" /><br />
    <div class="kronolithDialogInfo"><?php echo _("I.e. Dinner with John tomorrow 8pm") ?></div>
    <label><?php echo _("Add to") ?>:</label>
    <select id="kronolithQuickinsertCalendars">
    </select>
    <span class="kronolithSeparator">|</span>
    <input type="button" id="kronolithQuickinsertSave" value="<?php echo _("Save") ?>" class="button ok" />
    <span class="kronolithSeparator"><?php echo _("or") ?></span>
    <a href="#" id="kronolithQuickinsertCancel" class="kronolithFormCancel"><?php echo _("Cancel") ?></a><br />
  </form>
</div>

