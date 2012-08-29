<div id="kronolithViewWorkweek" style="display:none">

<div class="kronolithViewHead">
<table id="kronolithViewWorkweekHead" class="kronolithView kronolithViewDay">
  <thead><tr>
    <td class="kronolithFirstCol"><div id="kronolithLoadingworkweek" class="kronolithLoading"></div></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
    <td class="kronolithWeekDay"><span>&nbsp;</span></td>
  </tr></thead>
  <tbody>
    <tr class="kronolithAllDay">
      <td class="kronolithFirstCol"><span><?php echo _("All day") ?></span></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div class="kronolithViewBody">
<div class="kronolithViewBodyContainer">

<table class="kronolithView kronolithViewDay">
  <tbody id="kronolithViewWorkweekBody">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr<?php if ($i < 9 || $i > 19) echo ' class="kronolithNight"' ?>>
      <td class="kronolithFirstCol"><span><?php echo $ampm ? ($i % 12 ? ($i % 12): 12) : $i ?>:00</span></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
<div id="kronolithEventsWorkweek">
  <div class="kronolithEventsWorkweek"></div>
  <div class="kronolithEventsWorkweek"></div>
  <div class="kronolithEventsWorkweek"></div>
  <div class="kronolithEventsWorkweek"></div>
  <div class="kronolithEventsWorkweek"></div>
</div>

</div>
</div>

</div>
