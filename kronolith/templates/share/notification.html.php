<p><font size="4"><strong><?php printf(_("Invitation from %s to the calendar %s"), $this->h($this->user), $this->h($this->calendar)) ?></strong></font></p>

<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
  <td width="140" valign="top">
    <img src="cid:<?php echo $this->imageId ?>" />
  </td>
  <td valign="top">
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td>
          <font size="2">
            <?php printf(_("%s wants to share the calendar %s with you to grant you access to all events in this calendar."), '<strong>' . $this->h($this->user) . '</strong>', '<strong>' . $this->h($this->calendar) . '</strong>') ?>
            <?php if ($this->subscribe): ?>
            <?php echo _("To subscribe to this calendar, you need to click the following link:") ?>
            <br><br>
            <strong><a href="<?php echo $this->h($this->subscribe) ?>"><?php echo $this->h($this->subscribe) ?></a></strong>
            <?php endif ?>
          </font>
        </td>
      </tr>
    </table>
  </td>
</tr></table>
