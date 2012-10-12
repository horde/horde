<div id="kronolithViewMonth" style="display:none">
<table id="kronolithViewMonthHead" class="kronolith-view kronolith-view-head">
  <tr>
    <td class="kronolith-first-col"><div id="kronolithLoadingmonth" class="kronolithLoading"></div></td>
    <?php for ($w = $prefs->getValue('week_start_monday'), $i = $w, $c = $i + 7; $i < $c; $i++): ?>
    <td><?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?></td>
    <?php endfor; ?>
  </tr>
</table>

<div id="kronolithViewMonthContainer">
<table class="kronolith-view kronolithViewMonth">
<tbody id="kronolith-month-body">
  <tr id="kronolithRowTemplate" style="display:none">
    <td class="kronolith-first-col"></td>
    <td class="kronolithMonthDay<?php if ($w == 6 || $w == 0) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 5 || $w == 6) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 4 || $w == 5) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 3 || $w == 4) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 2 || $w == 3) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 1 || $w == 2) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 0 || $w == 1) echo ' kronolith-weekend' ?>">
      <div class="kronolith-day"></div>
    </td>
  </tr>
</tbody>
</table>
</div>
</div>
