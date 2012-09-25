<div data-role="page" id="monthview">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Month"))) ?>

 <div data-role="header">
  <div data-role="navbar">
   <ul>
    <li><a href="#dayview"><?php echo _("Day")?></a></li>
    <li><a href="#" class="ui-btn-active ui-state-persist"><?php echo _("Month")?></a></li>
    <li><a href="#overview"><?php echo _("Agenda")?></a></li>
   </ul>
  </div>
 </div>

 <div data-role="header">
  <a href="#minical-prev" data-icon="arrow-l" data-iconpos="notext"><?php echo _("Previous") ?></a>
  <h3 id="kronolithMinicalDate"><?php echo $this->today->format('F Y') ?></h3>
  <a href="#minical-next" data-icon="arrow-r" data-iconpos="notext"><?php echo _("Next") ?></a>
 </div>

 <div data-role="content" id="kronolith-minical" class="kronolith-minical">
  <table>
   <thead>
    <tr>
<?php for ($i = $GLOBALS['prefs']->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
     <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo Horde_String::substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
<?php endfor; ?>
    </tr>
   </thead>
   <tbody><tr><td></td></tr></tbody>
  </table>
 </div>

 <div id="kronolithDayDetailHeader" data-role="header">
   <h3></h3>
 </div>

 <div id="kronolithDayDetail" data-role="content"></div>
</div>
