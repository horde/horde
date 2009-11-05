<div id="kronolithViewDay" class="kronolithView kronolithViewDay" style="display:none">
<div id="kronolithLoadingday" class="kronolithLoading"></div>
<div id="kronolithEventsDay"></div>

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
<table class="kronolithView kronolithViewDay">
  <thead class="kronolithViewHead">
    <tr>
      <td class="kronolithFirstCol">&nbsp;</td>
      <td><span><?php echo _("Events for this day") ?></span></td>
    </tr>
  </thead>
  <tbody class="kronolithViewBody">
    <tr class="kronolithRow kronolithAllDay">
      <td class="kronolithFirstCol"><?php echo _("All day") ?></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div style="position: absolute; top: 150px; right: 0; bottom: 0; left: 0; overflow-x: hidden; overflow-y: scroll">
<table class="kronolithView kronolithViewDay">
  <tbody id="kronolithViewDayBody" class="kronolithViewBody fixme">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr class="kronolithRow<?php if ($i < 9 || $i > 19) echo ' kronolithNight' ?>">
      <td class="kronolithFirstCol"><?php echo $i ?>:00</td>
      <td></td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
</div>

</div>
