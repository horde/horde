<div id="kronolithViewWeek" class="kronolithView kronolithViewWeek" style="display:none">

<div class="kronolithViewHead">
  <div class="kronolithRow">
    <div class="kronolithFirstCol">&nbsp;</div>
    <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
    <div class="kronolithCol"><span><?php echo NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?></span></div>
    <?php endfor; ?>
  </div>
</div>
<div class="kronolithViewBody">
  <div class="kronolithRow">
    <div class="kronolithFirstCol"><?php echo _("All day") ?></div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
      <div class="kronolithEvent c3">Restauração da Independência</div>
      <div class="kronolithEvent c2">Dia Mundial da Usabilidade</div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
  </div>

<?php for ($i = 0; $i < 24; $i++): ?>
  <div class="kronolithRow">
    <div class="kronolithFirstCol"><?php printf('%02d', $i) ?></div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
    <div class="kronolithCol">
      <div class="kronolithCorner"><a href="javascript:edit();" class="kronolithAdd"><?php echo $img_blank ?></a></div>
    </div>
  </div>
<?php endfor; ?>

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
</div>

</div>
