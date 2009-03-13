<!-- ini header -->
<div id="kronolithHeader">
  <div id="kronolithLogo"><h1><a href="#"><?php echo $registry->get('name') ?></a></h1></div>

  <!-- ini user zone -->
  <div id="kronolithUser">
   <?php echo $identity->getName(); if ($logout_link) echo ' <span class="kronolithSeparator">&middot;</span> ' . $logout_link ?><br />
   <a href="#" id="kronolithToday"><?php echo _("Today") ?></a> <span class="kronolithSeparator">&middot;</span> <a href="#"><?php echo _("Options") ?></a><?php if ($help_link) echo ' <span class="kronolithSeparator">&middot;</span> ' . $help_link ?><br />
  </div>
  <!-- end user zone -->
</div>
<!-- end header -->

<!-- ini bar -->
<div id="kronolithBar">
  <form>
    <div class="kronolithDateChoice">
      <div id="kronolithDateChoiceDay"><?php echo $today->format('l, j') ?></div>
    </div>
    <div class="kronolithDateChoice">
      <div id="kronolithDateChoiceMonth"><?php echo $today->format('F') ?></div>
    </div>
    <div class="kronolithDateChoice">
      <div id="kronolithDateChoiceYear"><?php echo $today->format('Y') ?></div>
    </div>
  </form>

  <div id="kronolithNav">
    <a id="kronolithNavDay"><?php echo _("Day") ?></a>
    <a id="kronolithNavWeek"><?php echo _("Week") ?></a>
    <a id="kronolithNavMonth"><?php echo _("Month") ?></a>
    <a id="kronolithNavYear"><?php echo _("Year") ?></a>
    <a id="kronolithNavTasks"><?php echo _("Tasks") ?></a>
    <a id="kronolithNavAgenda"><?php echo _("Agenda") ?></a>
  </div>
</div>
<!-- end bar -->

<!-- ini main area -->
<div id="kronolithMain">

<!-- ini menu -->
<div id="kronolithMenu">
  <div id="kronolithAddEvents">
    <span id="kronolithNewEvent"><?php echo _("New event") ?></span>
    <span id="kronolithQuickEvent" onclick="$('kronolithQuickinsert').appear()">+</span>
  </div>

  <h3>
    <a href="#" class="kronolithAdd">+</a>
    <?php echo _("My Calendars") ?>
  </h3>

  <div id="kronolithMyCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <h3>
    <a href="#" class="kronolithAdd">+</a>
    <?php echo _("Shared Calendars") ?>
  </h3>

  <div id="kronolithSharedCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <div id="kronolithExternalCalendars"></div>

  <h3>
    <a href="#" class="kronolithAdd">+</a>
    <?php echo _("Remote Calendars") ?>
  </h3>

  <div id="kronolithRemoteCalendars" class="kronolithCalendars" style="display:none">
  </div>

  <div id="kronolithMinical">
    <table cellspacing="1" cellpadding="0" border="0">
    <caption>
      <a id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
      <a id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
      <span id="kronolithMinicalDate"><?php echo $today->format('F Y') ?></span>
    </caption>

    <thead>
      <tr>
        <th class="kronolithMinicalEmpty">&nbsp;</th>
        <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
        <th title="<?php echo NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo substr(NLS::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
        <?php endfor; ?>
      </tr>
    </thead>

    <tbody>
    </tbody>
    </table>
  </div>

  <h3>
    <a href="#" class="kronolithAdd"><?php echo _("Advanced") ?></a>
    <?php echo _("Search") ?>
  </h3>

  <form action="#" method="post">
    <input type="text" id="kronolithSearch" class="kronolithLongField" /><br />
    <select name="q_what">
      <option value="1"><?php echo _("Calendars") ?></option>
      <option value="2"><?php echo _("Events") ?></option>
    </select>
    <input type="submit" value="OK" class="button ok" /><br />
  </form>
</div>
<!-- end menu -->

<!-- ini edit -->
<div id="kronolithEventForm" style="display:none"><form action="#" method="post">
<div>
  <label for="id_ttl"><?php echo _("Title") ?></label><br />
  <input type="text" name="ttl" id="id_ttl" class="kronolithLongField" />
</div>

<table cellspacing="2" cellpadding="0" border="0"><tbody>
  <tr>
    <td class="rightAlign">
      <label for="id_local"><?php echo _("Location") ?></label><br />
      <small><a href="#"><?php echo _("Geolocation") ?></a></small>
    </td>
    <td><input type="text" name="local" id="id_local" value="Porto, Portugal" class="kronolithLongField" /></td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="id_fullday"><?php echo _("All-day") ?></label></td>
    <td><input type="checkbox" name="fullday" id="id_fullday" /></td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="id_from"><?php echo _("From") ?></label></td>
    <td>
      <input type="text" name="from_ymd" id="id_from" value="2006-10-30" size="9" class="kronolithDatePicker" />
      <?php echo _("at") ?>
      <input type="text" name="from_Hi" value="10:30" size="4" />
    </td>
  </tr>

  <tr>
    <td class="rightAlign">
      <label for="id_to"><?php echo _("To") ?></label></td>
    <td>
      <input type="text" name="to_ymd" id="id_to" value="2006-10-30" size="9" class="kronolithDatePicker" />
      <?php echo _("at") ?>
      <input type="text" name="to_Hi" value="10:30" size="4" />
    </td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="id_recur"><?php echo _("Repeat") ?></label></td>
    <td><select name="recur" id="id_recur" class="kronolithLongField">
      <option value="0"><?php echo _("no repeat") ?></option>
      <option value="1"><?php echo _("Every day") ?></option>
      <option value="2"><?php echo _("Every week") ?></option>
      <option value="3"><?php echo _("Every 15 days") ?></option>
      <option value="4" selected><?php echo _("Every year") ?></option>
      <option value="5"><?php echo _("Custom...") ?></option>
    </select></td>
  </tr>
</tbody></table>

<div>
  <label for="id_url"><?php echo _("URL") ?></label><br />
  <input type="text" name="url" id="id_url" class="kronolithLongField" />
</div>

<div>
  <label for="id_tags"><?php echo _("Tags") ?></label><br />
  <textarea name="tags" id="id_tags" rows="2" class="kronolithLongField"></textarea>
</div>

<div class="kronolithTags">
  <span>portugal</span>
  <span>política</span>
  <span>religião</span>
  <span>outra</span>
  <span>informática</span>
  <span>lálá</span>
</div>

<div id="kronolithEventActions">
  <input type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <input type="button" value="<?php echo _("Cancel") ?>" class="button cancel" />
  <span id="kronolithEventAdvancedLink">| <a href="#"><?php echo _("Advanced") ?></a></span><br />
</div>

</form></div>
<!-- end edit -->

<!-- ini body -->
<div id="kronolithBody">
<div id="kronolithLoading" style="display:none"></div>
<?php
$img_blank = Horde::img('blank.gif', '', 'height="8" width="8"', $registry->getImageDir('horde'));
require dirname(__FILE__) . '/month.inc';
require dirname(__FILE__) . '/week.inc';
require dirname(__FILE__) . '/day.inc';
?>
</div>
<!-- end body -->

</div>
<!-- end main area -->

<div id="kronolithQuickinsert" style="display:none">
  <form action="#" method="post">
    <input type="text" name="q" value="" /> <input type="button" value="ok" class="button ok" onclick="$('kronolithQuickinsert').fade()" /><br />
    <?php echo _("I.e. Dinner with John tomorrow 10am") ?><br />
  </form>
</div>

<div class="kronlithContextMenu" style="display:none">
  <a href="#">teste</a>
  <div class="kronolithSeparator"></div>
  <a href="#">teste</a>
  <a href="#">teste</a>
</div>

<?php if (!($browser->isBrowser('msie') && ($browser->getMajor() < 7))): ?>
<div id="hordeAlertslog">
 <div style="display:none">
  <ul>
   <li class="hordeNoalerts"><strong><?php echo _("No Alerts") ?></strong></li>
  </ul>
 </div>
</div>
<?php endif; ?>
