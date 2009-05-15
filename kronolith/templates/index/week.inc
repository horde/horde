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
    <a class="kronolithToday" title="<?php echo _("Today") ?>"><?php echo _("Today") ?></a>
    <a class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a>
    <a class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a>
    <span>Sun, April 5 - Sat, April 11 <!-- fmt: this should be the week span --></span>
  </caption>
  <thead id="kronolithViewWeekHead" class="kronolithViewHead"><tr>
    <td class="kronolithFirstCol">&nbsp;</td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
    <td class="kronolithCol kronolithWeekDay"><span></span></td>
  </tr></thead>
  <tbody id="kronolithViewWeekBody" class="kronolithViewBody">
    <tr class="kronolithRow kronolithAllDay">
      <td class="kronolithFirstCol"><?php echo _("All day") ?></td>
      <td class="kronolithCol<?php if ($w == 6 || $w == 0) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 5 || $w == 6) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 4 || $w == 5) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 3 || $w == 4) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 2 || $w == 3) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 1 || $w == 2) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
      <td class="kronolithCol<?php if ($w == 0 || $w == 1) echo ' kronolithWeekend' ?>"><div class="kronolithAllDayContainer">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </div></td>
    </tr>
    <?php for ($i = 0; $i < 24; $i++): ?>
    <tr class="kronolithRow<?php if ($i < 9 || $i > 19) echo ' kronolithNight' ?>">
      <td class="kronolithFirstCol"><?php printf('%02d', $i) ?></td>
      <td class="kronolithCol<?php if ($w == 6 || $w == 0) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 5 || $w == 6) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 4 || $w == 5) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 3 || $w == 4) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 2 || $w == 3) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 1 || $w == 2) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
      <td class="kronolithCol<?php if ($w == 0 || $w == 1) echo ' kronolithWeekend' ?>">
        <div class="kronolithCorner"><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
      </td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>

<!--
  <div class="kronolithEvents">
    <div class="kronolithEvent c1 kronolithEventFull col1 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c2 kronolithEventFull col2 hour1 length6">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c1 kronolithEventFull col3 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c1 kronolithEventFull col4 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c1 kronolithEventFull col5 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c1 kronolithEventFull col6 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>

    <div class="kronolithEvent c1 kronolithEventFull col7 hour0 length2">
      <div class="kronolithDragger kronolithDraggerTop"></div>
      <div class="kronolithEventInfo">10h diiaiaiai jkh gjhg fjkgh fkjgh kfjhg jkfhg kjfh g</div>
      <div class="kronolithDragger kronolithDraggerBottom"></div>
    </div>
  </div>
//-->

</div>
