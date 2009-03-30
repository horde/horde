<table id="kronolithViewHead" class="kronolithView" style="display:none">
  <tr>
    <td class="kronolithFirstCol">&nbsp;</td>
    <?php for ($w = $prefs->getValue('week_start_monday'), $i = $w, $c = $i + 7; $i < $c; $i++): ?>
    <td class="kronolithCol"><span><?php echo NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?></span></td>
    <?php endfor; ?>
  </tr>
</table>

<div id="kronolithViewMonthContainer">
<table id="kronolithViewMonth" class="kronolithView kronolithViewMonth" style="display:none;">
<tbody class="kronolithViewBody">
  <tr id="kronolithRowTemplate" class="kronolithRow" style="display:none">
    <td class="kronolithFirstCol"></td>
    <td class="kronolithCol<?php if ($w == 6 || $w == 0) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 5 || $w == 6) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 4 || $w == 5) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 3 || $w == 4) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 2 || $w == 3) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 1 || $w == 2) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
    <td class="kronolithCol<?php if ($w == 0 || $w == 1) echo ' kronolithWeekend' ?>">
      <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </td>
  </tr>
</tbody>
</table>
</div>