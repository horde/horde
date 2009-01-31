function addTag(resource, type, endpoint)
{
    if (!$('newtags-input_' + resource).value.blank()) {
        var params = new Object();
        params.imple="TagActions/action=add/resource=" + resource + "/type=" + type + "/tags=" + $('newtags-input_' + resource).value;
        new Ajax.Updater({success:'tags_' + resource},
                         endpoint,
                         {
                             method: 'post',
                             parameters: params,
                             onComplete: function() {$('newtags-input_' + resource).value = "";}
                         }
        );
    }

    return true;
}

function removeTag(resource, type, tagid, endpoint)
{
    var params = new Object();
    params.imple = "TagActions/action=remove/resource=" + resource + "/type=" + type + "/tags=" + tagid;
    new Ajax.Updater({success:'tags_' + resource},
                     endpoint,
                     {
                         method: 'post',
                         parameters: params
                     }
    );
    
    return true;
}

function toggleTags(domid)
{
	$('tag-show_' + domid).toggle();
	$('tag-hide_' + domid).toggle();
	$('tagnode_' + domid).toggle();
}