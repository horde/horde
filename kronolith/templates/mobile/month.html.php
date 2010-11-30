<div data-role="page" id="monthview" class="monthview">
  <div data-role="header">
    <h1><?php echo _("Month")?></h1>
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
    <div data-role="navbar" class="ui-bar-a">
      <ul>
        <li><a href="#dayview"><?php echo _("Day")?></a></li>
        <li><a href="#" class="ui-btn-active"><?php echo _("Month")?></a></li>
        <li><a href="#overview"><?php echo _("Summary")?></a></li>
      </ul>
    </div>
    <div class="kronolithMonthHeader ui-bar-a">
     <a href="#" data-role="button" data-icon="arrow-l" data-iconpos="notext" id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
     <span class="kronolithMinicalDate"><?php echo $this->today->format('F Y') ?></span>
     <a href="#" data-role="button" id="kronolithMinicalNext" data-icon="arrow-r" data-iconpos="notext" title="<?php echo _("Next month") ?>">&gt;</a>
    </div>
  </div>
  <div id="monthcontent">
    <div id="kronolithMinical" class="kronolithMinical">
      <table>
        <thead>
          <tr>
            <?php for ($i = $GLOBALS['prefs']->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
            <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody><tr><td></td></tr></tbody>
      </table>
    </div>
    <div class="kronolithDayDetail">
        <h4 class="ui-bar-a"></h4>
    </div>
  </div>
</div>
