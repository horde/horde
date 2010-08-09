<?php
if (empty($url)) {
    $url = Horde::applicationUrl('month.php', true)
        ->add(array('month' => $month, 'year' => $year));
}
?>
<form action="delete.php" method="post" id="deleteform" name="delete">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="year" value="<?php if (isset($year)) echo htmlspecialchars($year) ?>" />
<input type="hidden" name="month" value="<?php if (isset($month)) echo htmlspecialchars($month) ?>" />
<input type="hidden" name="mday" value="<?php if (isset($day)) echo htmlspecialchars($day) ?>" />
<input type="hidden" name="url" value="<?php echo htmlspecialchars($url) ?>" />
<input type="hidden" name="eventID" value="<?php echo htmlspecialchars($this->event->id) ?>" />
<input type="hidden" name="calendar" value="<?php echo htmlspecialchars($this->event->calendar) ?>" />
<input type="hidden" name="type" value="<?php echo htmlspecialchars($this->event->calendarType) ?>" />

<table class="striped" cellspacing="0">
<tr><td class="warning"><?php echo _("This is a recurring event. Delete the current event only, this<br />occurrence and all future occurences, or all occurences?") ?></td></tr>

<?php if (!empty($this->event->attendees)): ?>
<tr><td><input id="sendupdates_del" type="checkbox" class="checkbox" name="sendupdates" checked="checked" /><label for="sendupdates_del"> <?php echo _("Send a cancel notification to all attendees when deleting this event?") ?></label></td></tr>
<?php endif ?>

<tr><td>
 <input type="submit" class="button" name="current" value="<?php echo _("Current") ?>" />
 <input type="submit" class="button" name="future" value="<?php echo _("Future") ?>" />
 <input type="submit" class="button" name="all" value="<?php echo _("All") ?>" />
 <a class="button" href="<?php echo htmlspecialchars($url) ?>"><?php echo _("Cancel") ?></a>
</td></tr>
</table>

</form>
