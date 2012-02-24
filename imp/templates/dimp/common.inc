<div class="context" id="ctx_reply" style="display:none;">
 <a id="ctx_reply_reply"><span class="iconImg"></span><?php echo _("To Sender") ?></a>
 <a id="ctx_reply_reply_all"><span class="iconImg"></span><?php echo _("To All") ?></a>
 <a id="ctx_reply_reply_list"><span class="iconImg"></span><?php echo _("To List") ?></a>
</div>

<div class="context" id="ctx_forward" style="display:none;">
<?php if (!$prefs->isLocked('forward_default')): ?>
 <a id="ctx_forward_attach"><span class="iconImg"></span><?php echo _("As Attachment") ?></a>
 <a id="ctx_forward_body"><span class="iconImg"></span><?php echo _("In Body Text") ?></a>
 <a id="ctx_forward_both"><span class="iconImg"></span><?php echo _("Attachment and Body Text") ?></a>
 <div class="sep"></div>
<?php endif; ?>
 <a id="ctx_forward_editasnew"><span class="iconImg"></span><?php echo _("Edit as New") ?></a>
 <div class="sep"></div>
 <a id="ctx_forward_redirect"><span class="iconImg"></span><?php echo _("Redirect") ?></a>
</div>

<div class="context" id="ctx_contacts" style="display:none">
 <a id="ctx_contacts_new"><span class="iconImg"></span><?php echo _("New Message") ?></a>
 <a id="ctx_contacts_add"><span class="iconImg"></span><?php echo _("Add to Address Book") ?></a>
</div>

<div style="display:none">
 <span id="largeaddrspan">
  <span class="largeaddrtoggle">
   <span class="largeaddrlist">[<?php echo _("Show Addresses (%d)") ?>]</span>
   <span class="largeaddrlist" style="display:none">[<?php echo _("Hide Addresses") ?>]</span>
  </span>
  <span class="dispaddrlist" style="display:none">
   <span class="largeaddrlistlimit" style="display:none">[<?php echo _("Load All Addresses") ?>]</span>
  </span>
 </span>
</div>
