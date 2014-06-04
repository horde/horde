<div>
 <?php echo _("Your signature to use when composing with the HTML editor (if empty, the text signature will be used)") . ($this->img_limit ? ' (' . sprintf(_("maximum total image size is %s"), IMP::sizeFormat($this->img_limit)) . ')' : '') ?>:
</div>

<div class="fixed">
 <textarea id="signature_html" name="signature_html" rows="4" cols="80" class="fixed"><?php echo $this->h($this->signature) ?></textarea>
</div>
