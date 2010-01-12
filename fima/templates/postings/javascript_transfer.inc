<script type="text/javascript">
<!--

function Submit(action) {
    if (document.postings.type_from.options[document.postings.type_from.selectedIndex].value == document.postings.type_to.options[document.postings.type_to.selectedIndex].value) {
        alert("<?php echo _("Please select another posting type where to transfer postings to.") ?>");
        return false;
    }
    return true;
}

// -->
</script>
