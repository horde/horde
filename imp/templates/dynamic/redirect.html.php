<form id="redirect" name="redirect" style="display:none">
 <input type="hidden" id="composeCacheRedirect" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
 <div class="msgwrite">
  <div class="dimpActions dimpActionsCompose">
   <div>
    <?php echo $this->actionButton(array('icon' => 'Forward', 'id' => 'send_button_redirect', 'title' => _("Redirect"))) ?>
   </div>
  </div>

  <table>
   <tr id="redirect_sendto">
    <td class="label">
    <span><?php echo _("To:") ?></span>
    </td>
    <td class="sendtextarea">
     <textarea id="redirect_to" name="redirect_to" rows="1" cols="75"></textarea>
     <span id="redirect_to_loading_img" class="loadingImg" style="display:none"></span>
    </td>
   </tr>
  </table>
 </div>
</form>
