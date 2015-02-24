<p><font size="4"><strong><?php echo $this->h($this->header) ?></strong></font></p>
<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
  <td width="140" valign="top">
    <img src="cid:<?php echo $this->imageId ?>" />
  </td>
  <td valign="top">
    <table width="100%" border="0" cellpadding="5" cellspacing="0">
      <thead><tr>
        <th colspan="3" align="left"><font size="3"><strong><?php echo $this->h($this->task->name) ?></strong></font></th>
      </tr></thead>
      <tbody>
        <?php $i = 1 ?>

        <?php if (strlen($this->task->desc)): ?>

        <tr<?php if ($i++ % 2) echo ' bgcolor="#f1f1f1"' ?>>
          <td nowrap="nowrap" align="right" valign="top">
            <font size="2"><strong><?php echo _("Description:") ?></strong></font>
          </td>
          <td width="5">&nbsp;</td>
          <td width="100%"><font size="2"><strong><?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->task->desc, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null)) ?></strong></font></td>
        </tr>
        <?php endif ?>

      </tbody>
    </table>
  </td>
</tr></table>

<p><font size="2"><?php echo _("Attached is an iCalendar file with more information about the task. If your mail client supports iTip requests you can use this file to easily update your local copy of the task.") ?></font></p>

