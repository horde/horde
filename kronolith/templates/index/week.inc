<div id="kronolithViewWeek" class="kronolithView kronolithViewWeek" style="display:none">

<div id="kronolithEventsWeek">
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
  <div class="kronolithEventsWeek"></div>
</div>

<table class="kronolithView kronolithViewDay">
  <caption>
    <span>&nbsp;</span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday" title="<?php echo _("_Today") ?>"><?php echo _("Today") ?></a>
      <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
      <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
    </span>
  </caption>
</table>

<div style="position: relative; margin-right: 15px;">
<table id="kronolithViewWeekHead" class="kronolithView kronolithViewDay">
  <thead class="kronolithViewHead"><tr>
    <td class="kronolithFirstCol"><div id="kronolithLoadingweek" class="kronolithLoading"></div></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
  </tr></thead>
  <tbody class="kronolithViewBody">
    <tr class="kronolithRow kronolithAllDay">
      <td class="kronolithFirstCol"><?php echo _("All day") ?></td>
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

<div style="position: absolute; top: 150px; right: 0; bottom: 0; left: 0; overflow-x: hidden; overflow-y: scroll;">
<table class="kronolithView kronolithViewDay">
  <tbody id="kronolithViewWeekBody" class="kronolithViewBody fixme">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr class="kronolithRow<?php if ($i < 9 || $i > 19) echo ' kronolithNight' ?>">
      <td class="kronolithFirstCol"><?php echo $i ?>:00</td>
      <td<?php if ($w == 6 || $w == 0) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 5 || $w == 6) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 4 || $w == 5) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 3 || $w == 4) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 2 || $w == 3) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 1 || $w == 2) echo ' class="kronolithWeekend"' ?>>
      </td>
      <td<?php if ($w == 0 || $w == 1) echo ' class="kronolithWeekend"' ?>>
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
</div>

</div>
