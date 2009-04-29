<?php
/* Build the directory sources select widget. */

?>
<form name="skoli_searchform" action="search.php" method="get" onsubmit="RedBox.loading(); return true;">
<input type="hidden" name="actionID" value="search" />

<h1 class="header">
 <?php echo _("Search Criterias") ?>
</h1>

<div class="text" style="padding:1em">
<strong><?php echo Horde::label('class', _("Search in")) ?></strong>
<?php if (count($classes) > 1): ?>
 <select name="class" id="class" onchange="if (this.value) { document.skoli_searchform.actionID.value='<?php echo $actionID ?>'; RedBox.loading(); document.skoli_searchform.submit() }">
  <?php echo implode('', $class_options) ?>
 </select>
<?php else: ?>
 <strong><?php echo current($classes)->get('name') ?></strong>
<?php endif; ?>
<strong>&nbsp;<?php echo Horde::label('student', _("and")) ?></strong>
<select name="student" id="student">
 <?php echo implode('', $student_options) ?>
</select>
<strong>&nbsp;<?php echo Horde::label('type', _("for")) ?></strong>
<select name="type" id="type">
 <?php echo implode('', $type_options) ?>
</select><br />

<br />

<table cellspacing="0" cellpadding="0" width="100%">
 <tr>
  <td>
   <strong><?php echo Horde::label('stext', _("and for Entries with:")) ?></strong>
   <input type="text" size="30" id="stext" name="stext" value="<?php echo htmlspecialchars($search) ?>" />
  <td style="text-align: center;">
   <input type="submit" class="button" name="search" value="<?php echo _("Search") ?>" />
   <input type="reset" class="button" name="reset" value="<?php echo _("Reset to Defaults") ?>" />
  </td>
 </tr>
</table>
</div>
</form>
