<div>
 <?php echo _("The list of addresses that will NOT have their images blocked by default (enter each address on a new line)") ?>:
</div>

<div class="fixed">
 <textarea name="safe_addrs" rows="10" cols="80" class="fixed"><?php echo $this->h($this->safe_addrs) ?></textarea>
</div>
