<h3><?php echo $this->h($this->header) ?></h3>

<table style="border-collapse:collapse;border:1px solid #000" border="1">
  <thead><tr>
    <th colspan="2" style="font-weight:bold;font-size:120%;background-color:#ddd"><?php echo $this->h($this->event->getTitle()) ?></th>
  </tr></thead>
  <tbody>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?php echo _("Start:") ?></td>
      <td><?php echo $this->h($this->event->start->strftime('%x %X')) ?></td>
    </tr>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?php echo _("End:") ?></td>
      <td><?php echo $this->h($this->event->end->strftime('%x %X')) ?></td>
    </tr>
    <?php if (strlen($this->event->location)): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?php echo _("Location:") ?></td>
      <td><?php echo $this->h($this->event->location) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (strlen($this->event->description)): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?php echo _("Description:") ?></td>
         <td><?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->event->description, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null)) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($this->attendees): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?php echo _("Attendees:") ?></td>
      <td>
        <?php foreach ($this->attendees as $attendee): ?>
        <?php if (strpos('@', $attendee) === false): ?>
        <?php echo $attendee ?><br />
        <?php else: ?>
        <a href="mailto:<?php echo $attendee ?>"><?php echo $attendee ?></a><br />
        <?php endif; ?>
        <?php endforeach; ?>
      </td>
    </tr>
    <?php endif; ?>
  </tbody>
</table>

<p><?php echo _("Attached is an iCalendar file with more information about the event. If your mail client supports iTip requests you can use this file to easily update your local copy of the event.") ?></p>

<p><?php printf(_("If your email client doesn't support iTip requests you can use the following links to: %saccept%s, %saccept tentatively%s or %sdecline%s the event."), '<strong><a href="' . htmlspecialchars($this->linkAccept) . '">', '</a></strong>', '<strong><a href="' . htmlspecialchars($this->linkTentative) . '">', '</a></strong>', '<strong><a href="' . htmlspecialchars($this->linkDecline) . '">', '</a></strong>') ?></p>
