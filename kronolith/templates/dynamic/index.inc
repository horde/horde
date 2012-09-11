<div id="horde-page" style="left:<?php echo $prefs->getValue('sidebar_width') ?>px">

<div id="kronolithQuickinsert" class="kronolithQuick" style="display:none">
  <h2><?php echo _("Quick Event Creation") ?></h2>
  <form id="kronolithQuickinsertForm" action="">
    <input type="text" id="kronolithQuickinsertQ" size="35" value="" class="kronolithLongField" /><br />
    <div class="kronolithDialogInfo"><?php echo _("I.e. Dinner with John tomorrow 8pm") ?></div>
    <label><?php echo _("Add to") ?>:</label>
    <select id="kronolithQuickinsertCalendars">
    </select>
    <br />
    <input type="button" id="kronolithQuickinsertSave" value="<?php echo _("Save") ?>" class="horde-default" />
    <span class="kronolithSeparator"><?php echo _("or") ?></span>
    <a href="#" id="kronolithQuickinsertCancel" class="kronolithFormCancel"><?php echo _("Cancel") ?></a>
  </form>
</div>

<!-- begin header -->
<div id="kronolithHeader">

<!-- begin top bar -->
<div class="horde-buttonbar">
  <ul>
    <li>
      <a id="kronolithGotoToday" <?php $todayAccessKey = Horde::getAccessKey(_("_Today")); echo Horde::getAccessKeyAndTitle(_("_Today"), true) ?>>
        <?php echo $today->strftime($prefs->getValue('date_format_mini')) ?>
      </a>
    </li>
    <li><a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a></li>
    <li class="horde-active"><span id="kronolithCurrent">&nbsp;</span></li>
    <li><a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a></li>
    <li class="horde-icon"><a id="kronolithNavDay" accesskey="1"><?php echo _("Day") ?></a></li>
    <li class="horde-icon"><a id="kronolithNavWorkweek" accesskey="2"><?php echo _("Work Week") ?></a></li>
    <li class="horde-icon"><a id="kronolithNavWeek" accesskey="3"><?php echo _("Week") ?></a></li>
    <li class="horde-icon"><a id="kronolithNavMonth" accesskey="4"><?php echo _("Month") ?></a></li>
    <li class="horde-icon"><a id="kronolithNavYear" accesskey="5"><?php echo _("Year") ?></a></li>
    <?php if (Kronolith::hasApiPermission('tasks')): ?>
    <li class="horde-icon"><a id="kronolithNavTasks" accesskey="6"><?php echo _("Tasks") ?></a></li>
    <?php endif; ?>
    <li><a id="kronolithNavAgenda" accesskey="<?php echo Kronolith::hasApiPermission('tasks') ? 7 : 6 ?>"><?php echo _("Agenda") ?></a></li>
  </ul>
</div>
<!-- end top bar -->

</div>
<!-- end header -->

<!-- begin main area -->
<div id="kronolithMain">

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

</div>
