<div id="kronolithViewWeek" style="display:none">

<div class="kronolith-view-head">
<table id="kronolithViewWeekHead" class="kronolith-view kronolithViewDay">
  <thead><tr>
    <td class="kronolith-first-col"><div id="kronolithLoadingweek" class="kronolithLoading"></div></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
  </tr></thead>
  <tbody>
    <tr class="kronolith-all-day">
      <td class="kronolith-first-col"><span><?php echo _("All day") ?></span></td>
      <td<?php if ($w == 6 || $w == 0) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 5 || $w == 6) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 4 || $w == 5) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 3 || $w == 4) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 2 || $w == 3) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 1 || $w == 2) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
      <td<?php if ($w == 0 || $w == 1) echo ' class="kronolith-weekend"' ?>><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div class="kronolithViewBody">
<div class="kronolithViewBodyContainer">

<table class="kronolith-view kronolithViewDay">
  <tbody id="kronolithViewWeekBody">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr<?php if ($i < 9 || $i > 19) echo ' class="kronolithNight"' ?>>
      <td class="kronolith-first-col"><span><?php echo $ampm ? ($i % 12 ? ($i % 12): 12) : $i ?>:00</span></td>
      <td<?php if ($w == 6 || $w == 0) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 5 || $w == 6) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 4 || $w == 5) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 3 || $w == 4) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 2 || $w == 3) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 1 || $w == 2) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
      <td<?php if ($w == 0 || $w == 1) echo ' class="kronolith-weekend"' ?>>&nbsp;</td>
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
