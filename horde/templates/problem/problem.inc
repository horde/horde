<form method="post" enctype="multipart/form-data" id="problem_report" name="problem_report" action="problem.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" id="actionID" name="actionID" value="cancel_problem_report" />
<input type="hidden" name="return_url" value="<?php echo htmlspecialchars(Horde_Util::getFormData('return_url')) ?>" />

<h1 class="header"><?php echo _("Describe the Problem") ?></h1>
<table cellspacing="0" width="100%">
 <tr>
  <td class="light rightAlign"><?php echo Horde::label('name', _("Your Name")) ?></td>
  <td><input type="text" tabindex="1" id="name" name="name" value="<?php echo htmlspecialchars($name) ?>" size="70" /></td>
 </tr>
 <tr>
  <td class="light rightAlign"><?php echo Horde::label('email', _("Your Email Address")) ?></td>
  <td><input type="text" tabindex="2" id="email" name="email" value="<?php echo htmlspecialchars($email) ?>" size="70" /></td>
 </tr>
 <tr>
  <td class="light rightAlign"><?php echo Horde::label('subject', _("Short Summary")) ?></td>
  <td><input type="text" tabindex="3" id="subject" name="subject" value="<?php echo htmlspecialchars($subject) ?>" size="70" /></td>
 </tr>
<?php if (!empty($GLOBALS['conf']['problems']['attachments'])): ?>
 <tr>
  <td class="light rightAlign"><?php echo Horde::label('attachment', _("Attachment")) ?></td>
  <td><input type="file" tabindex="4" id="attachment" name="attachment" size="60" /></td>
 </tr>
<?php endif; ?>
 <tr>
  <td colspan="2" class="smallheader"><?php echo Horde::label('message', _("Full Description")) ?></td>
 </tr>
 <tr>
  <td>&nbsp;</td>
  <td><textarea tabindex="5" id="message" name="message" rows="20" cols="80"><?php echo htmlspecialchars($message) ?></textarea></td>
 </tr>
 <tr>
  <td>&nbsp;</td>
  <td class="light">
   <input class="button" type="submit" name="formsubmit" value="<?php echo _("Send Problem Report") ?>" />
   <input class="button" type="submit" name="formsubmit" value="<?php echo _("Cancel Problem Report") ?>" />
  </td>
 </tr>
</table>
</form>
