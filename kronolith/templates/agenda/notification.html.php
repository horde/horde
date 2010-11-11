<p><font size="4"><strong><?php echo $this->h(_("Your daily agenda")) ?></strong></font></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
  <td width="140" valign="top">
    <table width="110" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="117" align="center" valign="bottom" style="background:transparent url('cid:<?php echo $this->imageId ?>') no-repeat center center">
          <font style="font-size:60px"><strong><?php echo $this->date->mday ?></strong></font>
        </td>
      </tr>
      <tr>
        <td align="center"><font size="4"><strong><?php echo $this->date->strftime('%B') ?></strong></font></td>
      </tr>
    </table>
  </td>
  <td valign="top">
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
      <?php $i = 0; foreach ($this->events as $event): ?>

      <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
        <td nowrap="nowrap" align="right">
          <font size="2"><strong>
            <?php if ($event->isAllDay()): ?>
            <?php echo $this->h(_("All day")) ?>
            <?php else: ?>
            <?php echo $this->h($event->start->format($this->timeformat)) ?>
            <?php endif ?>

          </strong></font>
        </td>
        <td width="5">&nbsp;</td>
        <td width="100%"><font size="2"><strong><a href="<?php echo $event->getViewUrl(array(), true)->remove(session_name()) ?>"><?php echo $this->h($event->title) ?></a></strong></font></td>
      </tr>
      <?php endforeach ?>

    </table>
  </td>
</tr></table>

<?php if ($this->prefsUrl): ?>
<p><font size="1"><?php printf(_("You get this message because your calendar is configured to send you a daily agenda. You can change this if you %slogin to the calendar%s and change your preferences."), '<a href="' . $this->prefsUrl . '">', '</a>') ?></font></p>
<?php endif ?>
