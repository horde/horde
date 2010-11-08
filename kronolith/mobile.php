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
//       apps to avoid outputing all the prototype dependant stuff.
//
//Horde::addScriptFile('http://code.jquery.com/jquery-1.4.3.min.js', 'horde', array('external' => true));
//Horde::addScriptFile('http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js', 'horde', array('external' => true));

// Probably want to use a customized Kronolith::header() method as well instead
// of including a common-header-mobile file.

Horde::addInlineScript(Kronolith::includeJSVars());
$title = _("My Calendar");
require KRONOLITH_TEMPLATES . '/common-header-mobile.inc';
?>
<!-- Day View -->
<div data-role="page">
  <div data-role="header" data-position="fixed">
   <h1>My Calendar:Day</h1>
     <h3 id="todayheader"></h3>
  </div>
  <div data-role="content" class="ui-body" id="daycontent"></div>
  <div data-role="footer">
    <a href="mobile.php?test=1">Summary</a>
    <a href="mobile.php?test=2">Month</a>
    <a href="mobile.php?view=day" data-role="button">Day</a>
  </div>
</div>

<!-- Overview Page -->
<div data-role="page" id="overview">
  <div data-role="header" data-position="fixed">
   <h1>My Calendar:List</h1>
  </div>
  <div data-role="content" class="ui-body"></div>
</div>

<!-- Singe Event View -->
<div data-role="page" id="eventview">
    <div data-role="header" data-position="fixed"><h1>Event</h1></div>
    <div data-role="content" class="ui-body" id="eventcontent"></div>
    <div data-role="footer"></div>
</div>

<!-- Month View -->
<div data-role="page" id="monthview">
    <div data-role="header" data-position="fixed"><h1>Event</h1></div>
    <div data-role="content" class="ui-body" id="monthcontent"></div>
    <div data-role="footer"></div>
</div>

<script type="text/javascript">
    $(function() {
       $.ajaxSetup({
           dataFilter: function(data, type)
           {
               filter = /^\/\*-secure-([\s\S]*)\*\/s*$/;
               return data.replace(filter, "$1");
           }
       });

       // Initial day view:
       Kronolith.now = new Date();
        $('body').bind('swipeleft',
            function(e) {
                Kronolith.now.addDays(1);
               $.post('/horde/services/ajax.php/kronolith/listEvents',
                      {'start': Kronolith.now.toString("yyyyMMdd"), 'end': Kronolith.now.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                      function(data) {
                          Kronolith.updateView(data.response);
                      },
                      'json'
               ); 
            });

        $('body').bind('swiperight',
            function(e) {
               Kronolith.now.addDays(-1);
               $.post('/horde/services/ajax.php/kronolith/listEvents',
                      {'start': Kronolith.now.toString("yyyyMMdd"), 'end': Kronolith.now.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
                      function(data)
                      {
                          Kronolith.updateView(data.response);
                      },
                      'json'
               );
            });

       $.post('/horde/services/ajax.php/kronolith/listEvents',
              {'start': Kronolith.now.toString("yyyyMMdd"), 'end': Kronolith.now.toString("yyyyMMdd"), 'cal': Kronolith.conf.default_calendar},
              function(data) {
                  Kronolith.updateView(data.response);
              },
              'json'
       );
    });

    /**
     * Updates the day view.
     * @TODO: LOTS!! (Sort events, multiple calendars etc...)
     */
    Kronolith.updateView = function(data)
    {
        $("#daycontent ul").detach();
        $("#todayheader").html(Kronolith.now.toString(Kronolith.conf.date_format));
        var list = $('<ul>').attr({'data-role': 'listview'});
        var type = data.cal.split('|')[0], cal = data.cal.split('|')[1];
        if (data.events) {
            $.each(data.events, function(datestring, events) {
                $.each(events, function(index, event) {
                    // set .text() first, then .html() to escape
                    var d = $('<div style="color:' + Kronolith.conf.calendars[type][cal].bg + '">');
                    var item = $('<li>').append();
                    d.text(Date.parse(event.s).toString(Kronolith.conf.time_format)
                        + ' - '
                        + Date.parse(event.e).toString(Kronolith.conf.time_format)
                        + ' '
                        + event.t).html();
                    var a = $('<a>').attr({'href': '#eventview'}).click(function(e) {
                        Kronolith.loadEvent(data.cal, index, Date.parse(event.e));
                    }).append(d);
                    list.append(item.append(a));
                });
            });
            list.listview();
            $("#daycontent").append(list);
        }
    };

    /**
     * Requests a single event from the backend, the (will eventually) populate
     * the event view.
     */
    Kronolith.loadEvent = function(cal, idy, d)
    {
        $.post(Kronolith.conf.URI_AJAX + 'getEvent',
               {'cal': cal, 'id': idy, 'date': d.toString('yyyyMMdd')},
               function(data)
               {
                   $("#eventcontent").text(data.response.event.t);
               },
               'json');
    };
</script>
<?php  $registry->get('templates', 'horde') . '/common-footer-mobile.inc';
