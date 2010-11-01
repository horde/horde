<div id="kronolithViewWeek" style="display:none">

<table class="kronolithView kronolithViewDay">
  <caption>
    <span>&nbsp;</span>
    <span class="kronolithNavigation">
      <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
      <a class="kronolithGotoToday"><?php echo Horde::highlightAccessKey(_("_Today"), $todayAccessKey) ?></a>
      <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
      <a class="kronolithPrint" onclick="print()" title="<?php echo _("Print") ?>"><span><?php echo _("Print") ?></span></a>
    </span>
  </caption>
</table>

<div class="kronolithViewHead">
<table id="kronolithViewWeekHead" class="kronolithView kronolithViewDay">
  <thead><tr>
    <td class="kronolithFirstCol"><div id="kronolithLoadingweek" class="kronolithLoading"></div></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
  </tr></thead>
  <tbody>
    <tr class="kronolithAllDay">
      <td class="kronolithFirstCol"><span><?php echo _("All day") ?></span></td>
      <td<?php if ($w == 6 || $w == 0) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 5 || $w == 6) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 4 || $w == 5) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 3 || $w == 4) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 2 || $w == 3) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 1 || $w == 2) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 0 || $w == 1) echo ' class="kronolithWeekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div class="kronolithViewBody">
<div class="kronolithViewBodyContainer">

<table class="kronolithView kronolithViewDay">
  <tbody id="kronolithViewWeekBody">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr<?php if ($i < 9 || $i > 19) echo ' class="kronolithNight"' ?>>
      <td class="kronolithFirstCol"><span><?php echo $i ?>:00</span></td>
      <td<?php if ($w == 6 || $w == 0) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 5 || $w == 6) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 4 || $w == 5) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 3 || $w == 4) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 2 || $w == 3) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 1 || $w == 2) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 0 || $w == 1) echo ' class="kronolithWeekend"' ?>>&nbsp;</td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
<div id="kronolithEventsWeek">
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
</div>

</div>
</div>

</div>
