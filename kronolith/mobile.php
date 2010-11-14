<?php
/**
 * Kronolith Mobile View
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @pacakge Kronolith
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

//@TODO: Will eventually need a separate Horde::includeScriptFiles for mobile
//       apps to avoid outputing all the prototype dependent stuff.
//
//Horde::addScriptFile('http://code.jquery.com/jquery-1.4.3.min.js', 'horde', array('external' => true));
//Horde::addScriptFile('http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js', 'horde', array('external' => true));

// Probably want to use a customized Kronolith::header() method as well instead
// of including a common-header-mobile file.

$title = _("My Calendar");
require $registry->get('templates', 'horde') . '/common-header-mobile.inc';
$today = new Horde_Date($_SERVER['REQUEST_TIME']);
?>
  <?php Horde::addInlineScript(Kronolith::includeJSVars());?>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'horde') ?>/date/en-US.js"></script>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'horde') ?>/date/date.js"></script>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'kronolith') ?>/kronolithmobile.js"></script>
  <link href="/horde/kronolith/themes/screen.css" rel="stylesheet" type="text/css" />
</head>
<body>

<!-- Initial, Day view -->
<div data-role="page" id="dayview">
  <div data-role="header">
   <h1>My Calendar:Day</h1>
   <a class="ui-btn-left" href="<?php echo Horde::getServiceLink('portal', 'horde')?>"><?php echo _("Home")?></a>
   <a rel="external" class="ui-btn-right" href="<?php echo Horde::getServiceLink('logout', 'horde')?>"><?php echo _("Logout")?></a>
   <div class="ui-bar-b" style="width:100%;text-align:center;"><a href="#" data-icon="arrow-l" data-iconpos="notext" id="prevDay"><?php echo _("Previous")?></a><span id="todayheader"></span><a href="#" data-icon="arrow-r" data-iconpos="notext" id="nextDay"><?php echo _("Next")?></a></div>
  </div>
  <div data-role="content" class="ui-body" id="daycontent"></div>
  <div data-role="footer" data-position="fixed">
   <div data-role="navbar">
    <ul>
     <li><a href="#" class="ui-btn-active">Day</a></li>
     <li><a href="#monthview">Month</a></li>
     <li><a href="#overview">Summary</a></li>
    </ul>
   </div>
  </div>
</div>

<!-- Single Event -->
<div data-role="page" id="eventview">
  <div data-role="header" data-theme="b"><h1>Event</h1></div>
  <div data-role="content" class="ui-body" id="eventcontent"></div>
  <div data-role="footer"></div>
</div>

<!-- Overview Page -->
<div data-role="page" id="overview">
  <div data-role="header"><h1>My Calendar: Overview</h1></div>
  <div data-role="content" class="ui-body"></div>
  <div data-role="footer" data-position="fixed">
   <div data-role="navbar">
    <ul>
     <li><a href="#dayview">Day</a></li>
     <li><a href="#monthview">Month</a></li>
     <li><a href="#" class="ui-btn-active">Summary</a></li>
    </ul>
   </div>
  </div>
</div>

<!-- Month View -->
<div data-role="page" id="monthview" class="monthview">
 <div data-role="header"><h1>Month</h1></div>
 <div data-role="content" class="ui-body" id="monthcontent">
  <div id="kronolithMinical" class="kronolithMinical">
    <table>
    <caption>
      <a href="#" id="kronolithMinicalPrev" title="<?php echo _("Previous month") ?>">&lt;</a>
      <a href="#" id="kronolithMinicalNext" title="<?php echo _("Next month") ?>">&gt;</a>
      <span id="kronolithMinicalDate"><?php echo $today->format('F Y') ?></span>
    </caption>

    <thead>
      <tr>
        <?php for ($i = $prefs->getValue('week_start_monday'), $c = $i + 7; $i < $c; $i++): ?>
        <th title="<?php echo Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))) ?>"><?php echo substr(Horde_Nls::getLangInfo(constant('DAY_' . ($i % 7 + 1))), 0, 1) ?></th>
        <?php endfor; ?>
      </tr>
    </thead>

    <tbody><tr><td>test</td></tr></tbody>
    </table>
  </div>

 </div>
  <div data-role="footer" data-position="fixed">
   <div data-role="navbar">
    <ul>
     <li><a href="#dayview">Day</a></li>
     <li><a href="#" class="ui-btn-active">Month</a></li>
     <li><a href="#overview">Summary</a></li>
    </ul>
   </div>
  </div>
</div>

<?php $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
