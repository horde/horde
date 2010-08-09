<?php
if (empty($url)) {
    $url = Horde::applicationUrl('month.php', true)
        ->add(array('month' => $month, 'year' => $year));
}
?>
<form action="delete.php" method="post" name="delete">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="year" value="<?php if (isset($year)) echo htmlspecialchars($year) ?>" />
<input type="hidden" name="month" value="<?php if (isset($month)) echo htmlspecialchars($month) ?>" />
<input type="hidden" name="mday" value="<?php if (isset($day)) echo htmlspecialchars($day) ?>" />
<input type="hidden" name="url" value="<?php echo htmlspecialchars($url) ?>" />
<input type="hidden" name="eventID" value="<?php echo htmlspecialchars($this->event->id) ?>" />
<input type="hidden" name="calendar" value="<?php echo htmlspecialchars(Horde_Util::getFormData('calendar')) ?>" />
<input type="hidden" name="type" value="<?php echo htmlspecialchars($this->event->calendarType) ?>" />

<div class="headerbox">
<?php if (!empty($this->event->attendees)): ?>
 <p><input id="sendupdates_del" type="checkbox" class="checkbox" name="sendupdates" checked="checked" /><label for="sendupdates_del"> <?php echo _("Send a cancel notification to all attendees when deleting this event?") ?></label></p>
<?php endif ?>

 <p><?php echo _("Permanently delete this event?") ?></p>

 <input type="submit" class="button" name="delete" value="<?php echo _("Delete") ?>" />
 <a class="button" href="<?php echo htmlspecialchars($url) ?>"><?php echo _("Cancel") ?></a>
</div>

</form>
