<span id="mailboxLabel">
 <?php echo _("Mailbox:") ?>
 <span id="mailboxName"><?php echo _("None") ?></span>
 <span class="iconImg readonlyImg" style="display:none" title="<?php echo $this->h(_("Read-Only")) ?>"></span>
</span>
<?php if ($this->quota): ?>
<span id="quota-text"></span>
<?php endif ?>
