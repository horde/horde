<div id="search" data-role="dialog">
 <div data-role="header">
  <h1><?php echo _("Search") ?></h1>
 </div>

 <div data-role="content" class="ui-body">
  <form id="imp-search-form" action="#">
   <label for="imp-search-by"><?php echo _("Search By") ?></label>
   <select id="imp-search-by" name="">
    <option value="all"><?php echo _("Entire Message") ?></option>
    <option value="body"><?php echo _("Body") ?></option>
    <option value="from"><?php echo _("From") ?></option>
    <option value="recip"><?php echo _("Recipients (To/Cc/Bcc)") ?></option>
    <option value="subject"><?php echo _("Subject") ?></option>
   </select>
   <input type="text" id="imp-search-input" />
   <a href="" data-role="button" id="imp-search-submit"><?php echo _("Start Search") ?></a>
  </form>
 </div>
</div>
