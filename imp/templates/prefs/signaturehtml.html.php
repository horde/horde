<div>
 <?php echo _("Your signature to use when composing with the HTML editor (if empty, the text signature will be used)") ?>:
</div>

<div class="fixed">
 <textarea id="signature_html" name="signature_html" rows="4" cols="80" class="fixed"><?php echo $this->h($this->signature) ?></textarea>
</div>
