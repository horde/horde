<p><font size="4"><strong><?php echo $this->h($this->header) ?></strong></font></p>
<p><font size="2"><?php echo $this->h($this->details) ?></font></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
  <td width="140" valign="top">
    <img src="cid:<?php echo $this->imageId ?>" />
  </td>
  <td valign="top">
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
      <thead><tr>
        <th colspan="3" align="left"><font size="3"><strong><?php echo $this->h($this->event->getTitle()) ?></strong></font></th>
      </tr></thead>
      <tbody>
        <?php $i = 1 ?>

        <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
          <td nowrap="nowrap" align="right">
            <font size="2"><strong><?php echo _("Calendar:") ?></strong></font>
          </td>
          <td width="5">&nbsp;</td>
          <td width="100%"><font size="2"><strong><?php echo $this->h($this->calendar) ?></strong></font></td>
        </tr>
        <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
          <td nowrap="nowrap" align="right">
            <font size="2"><strong><?php echo _("Start:") ?></strong></font>
          </td>
          <td width="5">&nbsp;</td>
          <td width="100%"><font size="2"><strong><?php echo $this->event->start->strftime('%x %X') ?></strong></font></td>
        </tr>
        <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
          <td nowrap="nowrap" align="right">
            <font size="2"><strong><?php echo _("End:") ?></strong></font>
          </td>
          <td width="5">&nbsp;</td>
          <td width="100%"><font size="2"><strong><?php echo $this->event->end->strftime('%x %X') ?></strong></font></td>
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
        <?php if (count($this->event->attendees)): ?>

        <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
          <td nowrap="nowrap" align="right" valign="top">
            <font size="2"><strong><?php echo _("Attendees:") ?></strong></font>
          </td>
          <td width="5">&nbsp;</td>
          <td width="100%"><font size="2"><strong>
            <?php foreach ($this->event->attendees as $attendee): ?>
            <?php if (is_null($attendee->addressObject->host)): ?>
            <?php echo $this->h($attendee->displayName) ?><br />
            <?php else: ?>
            <a href="mailto:<?php echo $this->h($attendee->email) ?>"><?php echo $this->h($attendee->displayName) ?></a><br />
            <?php endif ?>
            <?php endforeach ?>

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

      </tbody>
    </table>
  </td>
</tr></table>

<?php if ($this->prefsUrl): ?>
<p><font size="1"><?php printf(_("You get this message because your calendar is configured to notify of new, edited, and deleted events. You can change this if you %slogin to the calendar%s and change your preferences."), '<a href="' . $this->prefsUrl . '">', '</a>') ?></font></p>
<?php endif ?>
