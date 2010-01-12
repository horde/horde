<div id="kronolithViewDay" style="display:none">
<div id="kronolithLoadingday" class="kronolithLoading"></div>

<table class="kronolithView kronolithViewDay">
  <caption>
    <span>&nbsp;</span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday"><?php echo Horde::highlightAccessKey(_("_Today"), $todayAccessKey) ?></a>
      <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
      <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
    </span>
  </caption>
</table>

<div class="kronolithViewHead">
<table class="kronolithView kronolithViewDay">
  <thead>
    <tr>
      <td class="kronolithFirstCol">&nbsp;</td>
      <td><span><?php echo _("Events for this day") ?></span></td>
    </tr>
  </thead>
  <tbody>
    <tr class="kronolithAllDay">
      <td class="kronolithFirstCol"><span><?php echo _("All day") ?></span></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div class="kronolithViewBody">
<table class="kronolithView kronolithViewDay">
  <tbody id="kronolithViewDayBody">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr<?php if ($i < 9 || $i > 19) echo ' class="kronolithNight"' ?>>
      <td class="kronolithFirstCol"><span><?php echo $i ?>:00</span></td>
      <td></td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
<div id="kronolithEventsDay"></div>
</div>

</div>
