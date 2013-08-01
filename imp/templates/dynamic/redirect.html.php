<form id="redirect" name="redirect" style="display:none">
 <?php echo $this->hiddenFieldTag('composeCache', null, array('id' => 'composeCacheRedirect')) ?>

 <div class="horde-buttonbar">
  <div class="iconImg headercloseimg closeImg" id="redirect_close"></div>
  <ul><li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Forward', 'id' => 'send_button_redirect', 'title' => _("Redirect"))) ?>
  </li></ul>
 </div>

 <table>
  <tr id="redirect_sendto">
   <td class="label">
    <span><?php echo _("To:") ?></span>
   </td>
   <td class="sendtextarea">
    <?php echo $this->textAreaTag('redirect_to', null, array('size' => '75x1')) ?>
    <span id="redirect_to_loading_img" class="loadingImg" style="display:none"></span>
   </td>
  </tr>
 </table>
</form>
