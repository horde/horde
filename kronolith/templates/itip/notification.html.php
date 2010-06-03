<h3><?= $this->h($this->header) ?></h3>

<table style="border-collapse:collapse;border:1px solid #000" border="1">
  <thead><tr>
    <th colspan="2" style="font-weight:bold;font-size:120%;background-color:#ddd"><?= $this->h($this->event->getTitle()) ?></th>
  </tr></thead>
  <tbody>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?= _("Start:") ?></td>
      <td><?= $this->h($this->event->start->strftime('%x %X')) ?></td>
    </tr>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?= _("End:") ?></td>
      <td><?= $this->h($this->event->end->strftime('%x %X')) ?></td>
    </tr>
    <? if (strlen($this->event->location)): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?= _("Location:") ?></td>
      <td><?= $this->h($this->event->location) ?></td>
    </tr>
    <? endif; ?>
    <? if (strlen($this->event->description)): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?= _("Description:") ?></td>
         <td><?= Horde_Text_Filter::filter($this->event->description, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null, 'class' => null, 'charset' => Horde_Nls::getCharset())) ?></td>
    </tr>
    <? endif; ?>
    <? if ($this->attendees): ?>
    <tr>
      <td style="font-weight:bold;vertical-align:top"><?= _("Attendees:") ?></td>
      <td>
        <? foreach ($this->attendees as $attendee): ?>
        <? if (strpos('@', $attendee) === false): ?>
        <?= $attendee ?><br />
        <? else: ?>
        <a href="mailto:<?= $attendee ?>"><?= $attendee ?></a><br />
        <? endif; ?>
        <? endforeach; ?>
      </td>
    </tr>
    <? endif; ?>
  </tbody>
</table>

<p><?= _("Attached is an iCalendar file with more information about the event. If your mail client supports iTip requests you can use this file to easily update your local copy of the event.") ?></p>

<p><? printf(_("If your email client doesn't support iTip requests you can use the following links to: %saccept%s, %saccept tentatively%s or %sdecline%s the event."), '<strong><a href="' . htmlspecialchars($this->linkAccept) . '">', '</a></strong>', '<strong><a href="' . htmlspecialchars($this->linkTentative) . '">', '</a></strong>', '<strong><a href="' . htmlspecialchars($this->linkDecline) . '">', '</a></strong>') ?></p>
