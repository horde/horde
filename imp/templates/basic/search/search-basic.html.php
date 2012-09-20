<form method="post" action="<?php echo $this->action ?>">
 <?php echo $this->hiddenFieldTag('mailbox', $this->mbox) ?>
 <?php echo $this->hiddenFieldTag('search_basic', 1) ?>
 <?php echo $this->forminput ?>

 <h1 class="header">
  <strong><?php echo $this->search_title ?></strong>
 </h1>

 <table class="item">
  <tr>
   <td class="searchUILabel"><?php echo _("Search Criteria:") ?></td>
   <td>
    <select name="search_criteria">
     <option value=""><?php echo _("None") ?></option>
     <option value="" disabled="disabled">- - - - - - - - - -</option>
     <option value="from"><?php echo _("From") ?></option>
     <option value="recip"><?php echo _("Recipients (To/Cc/Bcc)") ?></option>
     <option value="subject"><?php echo _("Subject") ?></option>
     <option value="body"><?php echo _("Message Body") ?></option>
     <option value="text"><?php echo _("Entire Message") ?></option>
    </select>
    <input type="text" name="search_criteria_text" size="30" />
    <input type="checkbox" class="checkbox" name="search_criteria_not" />
    <label for="search_criteria_not"><?php echo _("Do NOT Match") ?></label>
   </td>
  </tr>
  <tr>
   <td class="searchUILabel"><?php echo _("Search Flags:") ?></td>
   <td>
    <select name="search_criteria_flag">
     <option value=""><?php echo _("None") ?></option>
     <option value="" disabled="disabled">- - - - - - - - - -</option>
<?php foreach ($this->flist as $v): ?>
     <option value="<?php echo $v['val'] ?>"><?php echo $v['label'] ?></option>
<?php endforeach; ?>
    </select>
    <input type="checkbox" class="checkbox" name="search_criteria_flag_not" />
    <label for="search_criteria_flag_not"><?php echo _("Do NOT Match") ?></label>
   </td>
  </tr>
  <tr>
   <td colspan="2">
    <?php echo $this->advsearch ?><?php echo _("Go to Advanced Search Page...") ?></a>
   </td>
  </tr>
 </table>

 <div>
  <input type="submit" class="button basicSearchSubmit" value="<?php echo _("Submit") ?>" />
  <input type="reset" class="button basicSearchReset" value="<?php echo _("Reset") ?>" />
 </div>
</form>
