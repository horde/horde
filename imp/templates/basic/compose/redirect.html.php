<form method="post" action="<?php echo $this->post_action ?>" id="redirect" name="redirect">
 <?php echo $this->hiddenFieldTag('actionID', 'redirect_send') ?>
 <?php echo $this->hiddenFieldTag('composeCache', $this->cacheid) ?>
 <?php echo $this->hiddenFieldTag('compose_requestToken', $this->token) ?>

 <?php echo $this->status ?>

 <h1 class="header">
  <?php echo $this->h($this->title) ?>
 </h1>

 <table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php if (isset($this->abook)): ?>
  <tr>
   <td></td>
   <td class="item">
    <table cellspacing="0" width="100%">
     <tr>
      <td align="center">
       <?php echo $this->abook . $this->hordeImage('addressbook_browse.png') ?>
       <br />
       <?php echo _("Address Book") ?>
      </td>
     </tr>
    </table>
   </td>
  </tr>
<?php endif; ?>

  <tr>
   <td class="light rightAlign">
    <strong><?php echo $this->hordeLabel('to', _("To")) ?></strong>
   </td>
   <td class="item leftAlign">
    <table border="0" width="100%" cellpadding="0">
     <tr>
      <td class="leftAlign">
       <input type="text" id="to" size="55" name="to" value="<?php echo $this->h($this->input_value) ?>" />
       <span class="loadingImg" id="to_loading_img" style="display:none;"></span>
      </td>
      <td class="rightAlign">
       <?php echo $this->hordeHelp('imp', 'compose-to') ?>
      </td>
     </tr>
    </table>
   </td>
  </tr>

  <tr>
   <td></td>
   <td>
<?php if ($this->allow_compose): ?>
    <input name="btn_redirect" type="submit" class="horde-default" value="<?php echo _("Redirect") ?>" />
<?php endif; ?>
    <input name="btn_cancel_compose" type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
   </td>
  </tr>
 </table>
</form>
