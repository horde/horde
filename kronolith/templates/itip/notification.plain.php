<?= $this->subject ?> (<? printf(_("on %s at %s"), $this->event->start->strftime('%x'), $this->event->start->strftime('%X')) ?>)

<? if (strlen($this->event->location)): ?>
<?= _("Location:") ?> <?= $this->event->location ?>


<? endif; ?>
<? if ($this->attendees): ?>
<?= _("Attendees:") ?> <?= implode(', ', $this->attendees) ?>


<? endif; ?>
<? if (strlen($this->event->description)): ?>
<?= _("The following is a more detailed description of the event:") ?>


<?= $this->event->description ?>


<? endif; ?>
<?= _("Attached is an iCalendar file with more information about the event. If your mail client supports iTip requests you can use this file to easily update your local copy of the event.") ?>


<?= _("If your email client doesn't support iTip requests you can use one of the following links to accept or decline the event.") ?>


<?= _("To accept the event:") ?>

<?= $this->linkAccept ?>


<?= _("To accept the event tentatively:") ?>

<?= $this->linkTentative ?>


<?= _("To decline the event:") ?>

<?= $this->linkDecline ?>

