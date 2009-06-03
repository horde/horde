<div id="kronolithViewYear" class="kronolithView kronolithViewYear" style="display:none">
<table class="kronolithView kronolithViewYear">
  <caption>
    <div id="kronolithLoadingyear" class="kronolithLoading"></div>
    <span></span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday"><?php echo _("Today") ?></a>
      <a title="<?php echo _("Previous") ?>" class="kronolithPrev">&lt;</a>
      <a title="<?php echo _("Next") ?>" class="kronolithNext">&gt;</a>
    </span>
  </caption>
</table>

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

<table id="kronolithYearTemplate" style="display:none" cellspacing="1" cellpadding="0" border="0">
  <caption>
    <span></span>
  </caption>
  <thead><tr>
    <th class="kronolithMinicalEmpty">&nbsp;</th>
    <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
    <th title="<?php echo NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo substr(NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
    <?php endfor; ?>
  </tr></thead>
  <tbody>
  </tbody>
</table>

</div>
