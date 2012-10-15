<div id="kronolithViewDay" style="display:none">
<div id="kronolithLoadingday" class="kronolithLoading"></div>

<div class="kronolith-view-head">
<table class="kronolith-view kronolithViewDay">
  <thead>
    <tr>
      <td class="kronolith-first-col">&nbsp;</td>
      <td><?php echo _("Events for this day") ?></td>
    </tr>
  </thead>
  <tbody>
    <tr class="kronolith-all-day">
      <td class="kronolith-first-col"><span><?php echo _("All day") ?></span></td>
      <td><div class="kronolithAllDayContainer">
      </div></td>
    </tr>
  </tbody>
</table>
</div>

<div class="kronolithViewBody">
<div class="kronolithViewBodyContainer">

<table class="kronolith-view kronolithViewDay">
  <tbody id="kronolithViewDayBody">
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr<?php if ($i < 9 || $i > 19) echo ' class="kronolithNight"' ?>>
      <td class="kronolith-first-col"><span><?php echo $ampm ? ($i % 12 ? ($i % 12): 12) : $i ?>:00</span></td>
      <td>&nbsp;</td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>
<div id="kronolithEventsDay"></div>

</div>
</div>

</div>
