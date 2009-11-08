function addTag(resource, type, endpoint)
{
    if (!$('newtags-input_' + resource).value.blank()) {
        var params = { "params": "resource=" + resource + "/type=" + type + "/tags=" + $('newtags-input_' + resource).value };
        new Ajax.Updater({success:'tags_' + resource},
                         endpoint + "/action=add/post=params",
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
    var params = {"params": "resource=" + resource + "/type=" + type + "/tags=" + tagid };
    new Ajax.Updater({success:'tags_' + resource},
                     endpoint + "/action=remove/post=params",
                     {
                         method: 'post',
                         parameters: params
                     }
    );

    return true;
}
