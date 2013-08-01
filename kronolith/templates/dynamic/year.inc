<div id="kronolithViewYear" class="kronolith-view kronolithViewYear" style="display:none">

<span id="kronolithLoadingyear" class="kronolithLoading"></span>

<div id="kronolithYear0" class="kronolith-minical"></div>
<div id="kronolithYear1" class="kronolith-minical"></div>
<div id="kronolithYear2" class="kronolith-minical"></div>
<div id="kronolithYear3" class="kronolith-minical"></div>
<div id="kronolithYear4" class="kronolith-minical"></div>
<div id="kronolithYear5" class="kronolith-minical"></div>
<div id="kronolithYear6" class="kronolith-minical"></div>
<div id="kronolithYear7" class="kronolith-minical"></div>
<div id="kronolithYear8" class="kronolith-minical"></div>
<div id="kronolithYear9" class="kronolith-minical"></div>
<div id="kronolithYear10" class="kronolith-minical"></div>
<div id="kronolithYear11" class="kronolith-minical"></div>

<table id="kronolithYearTemplate" style="display:none">
  <thead>
    <tr class="kronolith-minical-nav">
      <th class="kronolithMinicalDate" colspan="8">&nbsp;</th>
    </tr>
    <tr>
      <th class="kronolith-minical-empty">&nbsp;</th>
      <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
      <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo Horde_String::substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 2) ?></th>
      <?php endfor; ?>
    </tr>
  </thead>
  <tbody><tr><td></td></tr></tbody>
</table>

</div>
