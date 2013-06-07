<h1 class="header">
 <?php printf(_("Diff for %s between %s and %s"), $this->link, $this->version1, $this->version2) ?>
</h1>

<div class="text headerbox" style="padding:5px">
 <pre><?php echo $this->diff ?></pre>
</div>
