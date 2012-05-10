<form action="<?php echo $this->url ?>" method="post">
 <input type="hidden" name="a" value="ds" />
 <input type="hidden" name="mailbox" value="<?php echo $this->mailbox ?>" />
 <p>
  <?php echo _("Search:")?>
  <input name="search" />
 </p>
 <p>
  <input type="submit" value="<?php echo _("Run Search") ?>" />
 </p>
</form>
