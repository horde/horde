<div id="kronolithViewMonth" class="kronolithView kronolithViewMonth" style="display:none">

<div class="kronolithViewHead">
  <div class="kronolithRow">
    <div class="kronolithFirstCol">&nbsp;</div>
    <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
    <div class="kronolithCol"><span><?php echo NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?></span></div>
    <?php endfor; ?>
  </div>
</div>

<div class="kronolithViewBody">
</div>
</div>

<div id="kronolithRowTemplate" class="kronolithRow" style="display:none">
  <div class="kronolithFirstCol"></div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
  <div class="kronolithCol">
    <div class="kronolithCorner"><div class="kronolithDay"></div><a class="kronolithAdd"><?php echo $img_blank ?></a></div>
  </div>
</div>
