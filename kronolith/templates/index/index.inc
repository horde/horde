<!-- begin header -->
<div id="kronolithHeader">
  <div id="kronolithLogo"><h1><a href="#"><?php echo $registry->get('name') ?></a></h1></div>

  <!-- begin services -->
  <div id="kronolithServices">
    <div id="kronolithApplications">
       <ul>
          <li><a href="#" id="appMail" title="<?php echo _("Mail") ?>"><?php echo _("Mail") ?></a></li>
          <li class="selected"><a href="#" id="appKronolith" title="<?php echo _("Kronolith") ?>"><?php echo _("Kronolith") ?></a></li>
          <li><a href="#" id="appTurba" title="<?php echo _("Turba") ?>"><?php echo _("Turba") ?></a></li>
       </ul>
       <span class="kronolithSeparator">|</span>
    </div>
    <a href="#" id="kronolithOptions"><?php echo _("Options") ?></a><?php if ($help_link) echo ' <span class="kronolithSeparator">&middot;</span> ' . $help_link; if ($logout_link) echo ' <span class="kronolithSeparator">|</span> ' . $logout_link ?>
  </div>
  <!-- end services -->

<!-- begin top bar -->
<div id="kronolithBar">
  <div class="kronolithDateChoice" <?php $todayAccessKey = Horde::getAccessKey(_("_Today")); echo Horde::getAccessKeyAndTitle(_("_Today"), true) ?>>
    <div><?php echo $today->strftime('%a, %e %b %Y') ?></div>
  </div>
  <a href="#" id="kronolithNotifications" class="kronolithNotifications"><?php echo _("no notifications") ?></a>
  <div id="kronolithNav">
    <a id="kronolithNavDay" accesskey="1"><span><?php echo _("Day") ?></span></a>
    <a id="kronolithNavWeek" accesskey="2"><span><?php echo _("Week") ?></span></a>
    <a id="kronolithNavMonth" accesskey="3"><span><?php echo _("Month") ?></span></a>
    <a id="kronolithNavYear" accesskey="4"><span><?php echo _("Year") ?></span></a>
    <?php if ($GLOBALS['registry']->hasInterface('tasks')): ?>
    <a id="kronolithNavTasks" accesskey="5"><span><?php echo _("Tasks") ?></span></a>
    <?php endif; ?>
    <a id="kronolithNavAgenda" accesskey="<?php echo $GLOBALS['registry']->hasInterface('tasks') ? 6 : 5 ?>"><span><?php echo _("Agenda") ?></span></a>
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
    <span id="kronolithNewEvent" class="button" accesskey="<?php echo ($ak = Horde::getAccessKey(_("_New event"))) ?>"><?php echo Horde::highlightAccessKey(_("_New event"), $ak) ?></span>
    <span id="kronolithQuickEvent" <?php echo Horde::getAccessKeyAndTitle(_("Quick _insert")) ?>>+</span>
  </div>

  <div id="kronolithMinical" class="kronolithMinical">
    <table cellspacing="1" cellpadding="0" border="0">
    <caption>
      <a id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
      <a id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
      <span id="kronolithMinicalDate"><?php echo $today->format('F Y') ?></span>
    </caption>

    <thead>
      <tr>
        <th class="kronolithMinicalEmpty">&nbsp;</th>
        <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
        <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
        <?php endfor; ?>
      </tr>
    </thead>

    <tbody><tr><td></td></tr></tbody>
    </table>
  </div>

  <form id="kronolithSearchForm" action="">
    <input type="text" id="kronolithSearchTerm" value="<?php echo _("Search") ?>" default="<?php echo _("Search") ?>" />
    <input id="kronolithSearchButton" type="image" src="<?php echo $GLOBALS['registry']->getImageDir() ?>/search.png" />
  </form>

  <div id="kronolithMenuCalendars">
  <h3 id="kronolithCalendarsFirst">
    <?php if (Horde_Auth::getAuth() && !$prefs->isLocked('default_share')): ?>
    <a href="#" id="kronolithAddinternal" class="kronolithAdd">+</a>
    <?php endif; ?>
    <span><?php echo _("My Calendars") ?></span>
  </h3>

  <div id="kronolithMyCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <h3>
    <?php if (Horde_Auth::getAuth() && !$prefs->isLocked('default_share')): ?>
    <a href="#" id="kronolithAddtasklists" class="kronolithAdd">+</a>
    <?php endif; ?>
    <span><?php echo _("My Task Lists") ?></span>
  </h3>

  <div id="kronolithMyTasklists" class="kronolithCalendars" style="display:none">
  </div>

  <h3>
    <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddinternalshared" class="kronolithAdd">+</a>-->
    <span><?php echo _("Shared Calendars") ?></span>
  </h3>

  <div id="kronolithSharedCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <h3>
    <!-- to be added when searching for shared calendars is implemented <a href="#" id="kronolithAddtasklistsshared" class="kronolithAdd">+</a>-->
    <span><?php echo _("Shared Task Lists") ?></span>
  </h3>

  <div id="kronolithSharedTasklists" class="kronolithCalendars" style="display:none">
  </div>

  <div id="kronolithExternalCalendars"></div>

  <h3>
    <a href="#" id="kronolithAddremote" class="kronolithAdd">+</a>
    <span><?php echo _("Remote Calendars") ?></span>
  </h3>

  <div id="kronolithRemoteCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <h3>
    <span><?php echo _("Holidays") ?></span>
  </h3>

  <div id="kronolithHolidayCalendars" class="kronolithCalendars" style="display:none">
  </div>
  </div>

</div>
<!-- end left menu -->

<!-- begin edit form -->
<?php require dirname(__FILE__) . '/edit.inc'; ?>
<!-- end edit form -->

<!-- begin body -->
<div id="kronolithBody">
<div id="kronolithLoading" style="display:none"></div>
<?php
$img_blank = Horde::img('blank.gif', '', 'height="8" width="8"', $registry->getImageDir('horde'));
require dirname(__FILE__) . '/month.inc';
require dirname(__FILE__) . '/week.inc';
require dirname(__FILE__) . '/day.inc';
require dirname(__FILE__) . '/year.inc';
if ($GLOBALS['registry']->hasInterface('tasks')) {
    require dirname(__FILE__) . '/tasks.inc';
    require dirname(__FILE__) . '/task.inc';
}
require dirname(__FILE__) . '/agenda.inc';
?>
<div id="kronolithViewIframe" style="display:none"></div>
</div>
<!-- end body -->

</div>
<!-- end main area -->

<div id="kronolithQuickinsert" style="display:none">
  <form id="kronolithQuickinsertForm" action="">
    <input type="text" id="kronolithQuickinsertQ" size="35" value="" class="kronolithLongField" /><br />
    <span class="kronolithFormInfo"><?php echo _("I.e. Dinner with John tomorrow 8pm") ?></span><br />
    <label><?php echo _("Add to") ?>:</label>
    <select id="kronolithQuickinsertCalendars">
    </select>
    <span class="kronolithSep">|</span>
    <input type="button" id="kronolithQuickinsertSave" value="<?php echo _("Save") ?>" class="button ok" />
    <span class="kronolithSep"><?php echo _("or") ?></span>
    <a href="#" id="kronolithQuickinsertCancel" class="kronolithFormCancel"><?php echo _("Cancel") ?></a><br />
  </form>
</div>

