<?php
$calendar_names = array();
foreach (Kronolith::displayedCalendars() as $calendar) {
    $calendar_names[] = htmlspecialchars($calendar->name());
}
?>
<div id="calendarPrint">
 <br />
 <h3><?php echo _("Calendars Printed: ") ?></h3>
 <p><?php echo implode(', ', $calendar_names) ?></p>
</div>
