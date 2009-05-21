<div id="kronolithViewYear" class="kronolithView kronolithViewYear" style="display:none">
<table class="kronolithView kronolithViewYear">
  <caption>
    <span><?php echo $today->format("Y") ?></span>
    <span class="kronolithNavigation">
      <a class="kronolithGotoToday"><?php echo _("Today") ?></a>
      <a title="<?php echo _("Previous") ?>" class="kronolithPrev">&lt;</a>
      <a title="<?php echo _("Next") ?>" class="kronolithNext">&gt;</a>
    </span>
  </caption>
</table>

<?php foreach (array("January", "February","March","April","May","June","July","August","September","October","November","December") as $month): ?>
<div class="kronolithMinical">
<table cellspacing="1" cellpadding="0" border="0">
  <caption>
    <span date="20090401"><?php echo $month ?></span>
  </caption>
  <thead><tr>
    <th class="kronolithMinicalEmpty">&nbsp;</th>
    <th title="<?php echo _("Sunday") ?>"><?php echo substr(_("Sunday"),0,1) ?></th>
    <th title="<?php echo _("Monday") ?>"><?php echo substr(_("Monday"),0,1) ?></th>
    <th title="<?php echo _("Tuesday") ?>"><?php echo substr(_("Tuesday"),0,1) ?></th>
    <th title="<?php echo _("Wednesday") ?>"><?php echo substr(_("Wednesday"),0,1) ?></th>
    <th title="<?php echo _("Thursday") ?>"><?php echo substr(_("Thursday"),0,1) ?></th>
    <th title="<?php echo _("Friday") ?>"><?php echo substr(_("Friday"),0,1) ?></th>
    <th title="<?php echo _("Saturday") ?>"><?php echo substr(_("Saturday"),0,1) ?></th>
  </tr></thead>
  <tbody>
    <tr><td class="kronolithMinicalWeek" date="20090329">13</td><td date="20090329" class="kronolithMinicalEmpty">29</td><td date="20090330" class="kronolithMinicalEmpty">30</td><td date="20090331" class="kronolithMinicalEmpty">31</td><td date="20090401" class="">1</td><td date="20090402" class="">2</td><td date="20090403" class="">3</td><td date="20090404" class="">4</td></tr>
    <tr><td class="kronolithMinicalWeek" date="20090405">14</td><td date="20090405">5</td><td date="20090406">6</td><td date="20090407">7</td><td date="20090408">8</td><td date="20090409">9</td><td date="20090410">10</td><td date="20090411">11</td></tr>
    <tr><td class="kronolithMinicalWeek" date="20090412">15</td><td date="20090412">12</td><td date="20090413">13</td><td date="20090414">14</td><td date="20090415">15</td><td date="20090416">16</td><td date="20090417">17</td><td date="20090418">18</td></tr>
    <tr><td class="kronolithMinicalWeek" date="20090419">16</td><td date="20090419">19</td><td date="20090420">20</td><td date="20090421">21</td><td date="20090422">22</td><td date="20090423">23</td><td date="20090424">24</td><td date="20090425">25</td></tr>
    <tr><td class="kronolithMinicalWeek" date="20090426">17</td><td date="20090426">26</td><td date="20090427">27</td><td date="20090428">28</td><td date="20090429">29</td><td date="20090430">30</td><td date="20090501" class="kronolithMinicalEmpty">1</td><td date="20090502" class="kronolithMinicalEmpty">2</td></tr>
  </tbody>
</table>
</div>
<?php endforeach; ?>

</div>
