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
?>
  <?php Horde::addInlineScript(Kronolith::includeJSVars());?>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'horde') ?>/date/en-US.js"></script>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'horde') ?>/date/date.js"></script>
  <script type="text/javascript" src="<?php echo $registry->get('jsuri', 'kronolith') ?>/kronolithmobile.js"></script>
</head>
<body>

<!-- Initial, Day view -->
<div data-role="page" id="dayview">
  <div data-role="header" data-position="fixed">
   <h1>My Calendar:Day</h1>
   <a class="ui-btn-left" href="<?php echo Horde::getServiceLink('portal', 'horde')?>"><?php echo _("Home")?></a>
   <a rel="external" class="ui-btn-right" href="<?php echo Horde::getServiceLink('logout', 'horde')?>"><?php echo _("Logout")?></a>
   <div class="ui-bar-b" style="width:100%;text-align:center;"><a href="#" data-icon="arrow-l" id="prevDay"><?php echo _("Previous")?></a><span id="todayheader"></span><a href="#" data-icon="arrow-r" id="nextDay"><?php echo _("Next")?></a></div>

  </div>
  <div data-role="content" class="ui-body" id="daycontent"></div>
  <div data-role="footer">
    <a href="#">Summary</a>
    <a href="#">Month</a>
    <a href="#" data-role="button">Day</a>
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
  <div data-role="header" data-position="fixed"><h1>My Calendar: Overview</h1></div>
  <div data-role="content" class="ui-body"></div>
  <div data-role="footer"></div>
</div>

<div data-role="page" id="monthview">
 <div data-role="header" data-position="fixed"><h1>Month</h1></div>
 <div data-role="content" class="ui-body" id="monthcontent"></div>
 <div data-role="footer"></div>
</div>

<?php $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
