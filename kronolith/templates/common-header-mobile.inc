<?php

header('Content-type: text/html; charset=UTF-8');
header('Vary: Accept-Language');

?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars(Horde_Util::nonInputVar('title')) ?></title>
<?php

/* CSS Files. */
$horde_css = $GLOBALS['injector']->getInstance('Horde_Themes_Css');
$horde_css->addStylesheet(
    $GLOBALS['registry']->get('jsfs', 'horde') . '/jquery.mobile/jquery.mobile.min.css',
    $GLOBALS['registry']->get('jsuri', 'horde') . '/jquery.mobile/jquery.mobile.min.css'
);
$horde_css->addThemeStylesheet('mobile.css');
Horde::includeStylesheetFiles(array(
    'nobase' => true
));

/* JS Files. */
$GLOBALS['injector']->getInstance('Horde_Script_Files')->prototypejs = false;
Horde::addScriptFile('jquery.mobile/jquery.min.js', 'horde');
Horde::addScriptFile('horde-jquery.js', 'horde');
Horde::addScriptFile('mobile.js', 'horde');
Horde::addScriptFile('date/' . $datejs, 'horde');
Horde::addScriptFile('date/date.js', 'horde');
Horde::addScriptFile('mobile.js', 'kronolith');
require KRONOLITH_TEMPLATES . '/mobile/javascript_defs.php';

/* Inline script. */
Horde::addInlineScript(
  '$(window.document).bind("mobileinit", function() {
      $.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";
      $.mobile.loadingMessage = "' . _("loading") . '";

      // Setup event bindings to populate views on pagebeforeshow
      KronolithMobile.date = new Date();
      $("#dayview").live("pagebeforeshow", function() {
          KronolithMobile.view = "day";
          $(".kronolithDayDate").html(KronolithMobile.date.toString("ddd") + " " + KronolithMobile.date.toString("d"));
          KronolithMobile.loadEvents(KronolithMobile.date, KronolithMobile.date, "day");
      });

      $("#monthview").live("pagebeforeshow", function(event, ui) {
        KronolithMobile.view = "month";
        // (re)build the minical only if we need to
        if (!$(".kronolithMinicalDate").data("date") ||
            ($(".kronolithMinicalDate").data("date").toString("M") != KronolithMobile.date.toString("M"))) {
            KronolithMobile.moveToMonth(KronolithMobile.date);
        }
      });

      $("#eventview").live("pageshow", function(event, ui) {
            KronolithMobile.view = "event";
      });

      // Set up overview
      $("#overview").live("pageshow", function(event, ui) {
          KronolithMobile.view = "overview";
          if (!KronolithMobile.haveOverview) {
              KronolithMobile.loadEvents(KronolithMobile.date, KronolithMobile.date.clone().addDays(7), "overview");
              KronolithMobile.haveOverview = true;
          }
      });

   });'
);
Horde::outputInlineScript();

Horde::addScriptFile('jquery.mobile/jquery.mobile.min.js', 'horde');
Horde::includeScriptFiles();
