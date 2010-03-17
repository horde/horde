<div id="kronolithViewMonth" style="display:none">
<table id="kronolithViewMonthHead" class="kronolithView">
  <caption>
    <span>&nbsp;</span>
    <span class="kronolithNavigation">
      <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
      <a class="kronolithGotoToday"><?php echo Horde::highlightAccessKey(_("_Today"), $todayAccessKey) ?></a>
      <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
      <a class="kronolithPrint" onclick="print()" title="<?php echo _("Print") ?>"><span><?php echo _("Print") ?></span></a>
    </span>
  </caption>
  <tr>
    <td class="kronolithFirstCol"><div id="kronolithLoadingmonth" class="kronolithLoading"></div></td>
    <?php for ($w = $prefs->getValue('week_start_monday'), $i = $w, $c = $i + 7; $i < $c; $i++): ?>
    <td><span><?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?></span></td>
    <?php endfor; ?>
  </tr>
</table>

<div id="kronolithViewMonthContainer">
<table class="kronolithView kronolithViewMonth">
<tbody id="kronolithViewMonthBody">
  <tr id="kronolithRowTemplate" style="display:none">
    <td class="kronolithFirstCol"></td>
    <td class="kronolithMonthDay<?php if ($w == 6 || $w == 0) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 5 || $w == 6) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 4 || $w == 5) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 3 || $w == 4) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 2 || $w == 3) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 1 || $w == 2) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
    <td class="kronolithMonthDay<?php if ($w == 0 || $w == 1) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div></div>
    </td>
  </tr>
</tbody>
</table>
</div>
</div>
