<div id="kronolithViewDay" class="kronolithView kronolithViewDay" style="display:none">
<div id="kronolithEventsDay"></div>
<table class="kronolithView kronolithViewDay">
  <caption>
    <span></span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday" title="<?php echo _("Today") ?>"><?php echo _("Today") ?></a>
      <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
      <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
    </span>
  </caption>
  <tbody id="kronolithViewDayBody" class="kronolithViewBody">
    <tr class="kronolithRow kronolithAllDay">
      <td class="kronolithFirstCol"><?php echo _("All day") ?></td>
      <td class="kronolithCol"></td>
    </tr>
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr class="kronolithRow<?php if ($i < 9 || $i > 19) echo ' kronolithNight' ?>">
      <td class="kronolithFirstCol"><?php printf('%02d', $i) ?></td>
      <td class="kronolithCol"></td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
</div>
