<div id="kronolithViewYear" class="kronolithView kronolithViewYear" style="display:none">

<span id="kronolithLoadingyear" class="kronolithLoading"></span>

<div id="kronolithYear0" class="kronolithMinical"></div>
<div id="kronolithYear1" class="kronolithMinical"></div>
<div id="kronolithYear2" class="kronolithMinical"></div>
<div id="kronolithYear3" class="kronolithMinical"></div>
<div id="kronolithYear4" class="kronolithMinical"></div>
<div id="kronolithYear5" class="kronolithMinical"></div>
<div id="kronolithYear6" class="kronolithMinical"></div>
<div id="kronolithYear7" class="kronolithMinical"></div>
<div id="kronolithYear8" class="kronolithMinical"></div>
<div id="kronolithYear9" class="kronolithMinical"></div>
<div id="kronolithYear10" class="kronolithMinical"></div>
<div id="kronolithYear11" class="kronolithMinical"></div>

<table id="kronolithYearTemplate" style="display:none">
  <thead>
    <tr class="kronolithMinicalNav">
      <th class="kronolithMinicalDate" colspan="8">&nbsp;</th>
    </tr>
    <tr>
      <th class="kronolithMinicalEmpty">&nbsp;</th>
      <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
      <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo Horde_String::substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
      <?php endfor; ?>
    </tr>
  </thead>
  <tbody><tr><td></td></tr></tbody>
</table>

</div>
