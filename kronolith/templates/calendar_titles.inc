<?php
$calendar_names = array();
foreach ($GLOBALS['display_calendars'] as $calendarId) {
    $calendar_names[] = htmlspecialchars($GLOBALS['all_calendars'][$calendarId]->name());
}
if (!empty($GLOBALS['display_resource_calendars'])) {
    $driver = Kronolith::getDriver('Resource');
    foreach ($GLOBALS['display_resource_calendars'] as $c) {
        $rc = $driver->getResource($driver->getResourceIdByCalendar($c));
        $calendar_names[] = htmlspecialchars($rc->get('name'));
    }
}
?>
<div id="calendarPrint">
 <br />
 <h3><?php echo _("Calendars Printed: ") ?></h3>
 <p><?php echo implode(', ', $calendar_names) ?></p>
</div>
