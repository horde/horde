<!-- Month View -->
<div data-role="page" id="monthview" class="monthview">
  <div data-role="header">
    <h1><?php echo _("Month")?></h1>
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <?php if ($this->logout): ?>
    <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
    <div data-role="navbar" class="ui-bar-b">
      <ul>
        <li><a href="#dayview"><?php echo _("Day")?></a></li>
        <li><a href="#" class="ui-btn-active"><?php echo _("Month")?></a></li>
        <li><a href="#overview"><?php echo _("Summary")?></a></li>
      </ul>
    </div>
  </div>
  <div data-role="content" class="ui-body" id="monthcontent">
    <div id="kronolithMinical" class="kronolithMinical">
      <table>
        <caption>
          <a href="#" id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
          <a href="#" id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
          <span id="kronolithMinicalDate"><?php echo $this->today->format('F Y') ?></span>
        </caption>
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
      <div class="spacer">&nbsp;</div>
    <div class="kronolithDayDetail ui-body">
        <ul data-role="listview" data-theme="d">
            <li>
                <a href="#">
                <div class="kronolithTimeWrapper">
                    <div class="kronolithStartTime">10:00 AM</div>
                    <div class="kronolithEndTime">11:00 AM</div>
                </div>
                <h2>Event Title</h2>
                <p class="kronolithDayLocation">Clayton, NJ</p>
                </a>
           </li>
        </ul>
    </div>
  </div>
</div>
