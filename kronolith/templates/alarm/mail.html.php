<p><font size="4"><strong><a href="<?php echo $this->event->getViewUrl(array(), true)->remove(session_name()) ?>"><?php echo $this->h($this->event->getTitle($this->user)) ?></a></strong></font></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
  <td width="140" valign="top">
    <img src="cid:<?php echo $this->imageId ?>" />
  </td>
  <td valign="top">
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
      <?php $i = 0 ?>

      <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
        <td nowrap="nowrap" align="right">
          <font size="2"><strong><?php echo _("Date and time:") ?></strong></font>
        </td>
        <td width="5">&nbsp;</td>
        <td width="100%"><font size="2"><strong><?php echo $this->event->start->strftime($this->dateFormat) ?>, <?php echo $this->event->start->format($this->timeFormat) ?></strong></font></td>
      </tr>
      <?php if (strlen($this->event->location)): ?>

      <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
        <td nowrap="nowrap" align="right">
          <font size="2"><strong><?php echo _("Location:") ?></strong></font>
        </td>
        <td width="5">&nbsp;</td>
        <td width="100%"><font size="2"><strong><?php echo $this->h($this->event->location) ?></strong></font></td>
      </tr>
      <?php endif ?>
      <?php if ($this->attendees): ?>

      <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
        <td nowrap="nowrap" align="right" valign="top">
          <font size="2"><strong><?php echo _("Attendees:") ?></strong></font>
        </td>
        <td width="5">&nbsp;</td>
        <td width="100%"><font size="2"><strong>
          <?php foreach ($this->attendees as $attendee): ?>
          <?php if (strpos('@', $attendee) === false): ?>
          <?php echo $this->h($attendee) ?><br />
          <?php else: ?>
          <a href="mailto:<?php echo $attendee ?>"><?php echo $this->h($attendee) ?></a><br />
          <?php endif; ?>
          <?php endforeach; ?>

        </strong></font></td>
      </tr>
      <?php endif ?>

      <?php if (strlen($this->event->description)): ?>

      <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
        <td nowrap="nowrap" align="right" valign="top">
          <font size="2"><strong><?php echo _("Description:") ?></strong></font>
        </td>
        <td width="5">&nbsp;</td>
        <td width="100%"><font size="2"><strong><?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->event->description, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null)) ?></strong></font></td>
      </tr>
      <?php endif ?>

    </table>
  </td>
</tr></table>

<?php if ($this->prefsUrl): ?>
<p><font size="1"><?php printf(_("You get this message because your calendar is configured to send you reminders of events with alarms. You can change this if you %slogin to the calendar%s and change your preferences."), '<a href="' . $this->prefsUrl . '">', '</a>') ?></font></p>
<?php endif ?>
