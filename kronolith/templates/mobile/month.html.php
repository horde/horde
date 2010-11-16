<!-- Month View -->
<div data-role="page" id="monthview" class="monthview">
 <div data-role="header">
   <h1>Month</h1>
   <a class="ui-btn-left" href="<?php echo $this->portal?>"><?php echo _("Home")?></a>
   <a rel="external" class="ui-btn-right" href="<?php echo $this->logout?>"><?php echo _("Logout")?></a>
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
 </div>
  <div data-role="footer" data-position="fixed">
   <div data-role="navbar">
    <ul>
     <li><a href="#dayview"><?php echo _("Day")?></a></li>
     <li><a href="#" class="ui-btn-active"><?php echo _("Month")?></a></li>
     <li><a href="#overview"><?php echo _("Summary")?></a></li>
    </ul>
   </div>
  </div>
</div>