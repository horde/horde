/**
 * Provides the javascript for managing folders.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpFolderPrefs = {
    // Variables defined by other code: folders

    newFolderName: function(f, fn, p1, p2)
    {
        f = $(f);
        fn = $(fn);

        if (f[f.selectedIndex].value == '') {
            var folder = window.prompt(p1, fn.value ? fn.value : '');
            if (folder != '') {
                fn.value = folder;
                f[1].text = p2 + ' [' + fn.value + ']';
            }
        }
    }

};

document.observe('dom:loaded', function() {
    var fp = ImpFolderPrefs;
    fp.folders.each(function(f) {
        $(f[0]).observe('change', fp.newFolderName.bind(fp, f[0], f[1], f[2], f[3]));
    });
});

// Called by Horde identity pref code.
function newChoice_sent_mail_folder(val)
{
    var field = $('sent_mail_folder');
    if (val == "") {
        field.selectedIndex = 0;
        return;
    }

    for (var i = 0, l = field.options.length; i < l; i++) {
        if (field.options[i].value == val) {
            field.selectedIndex = i;
            break;
        }
    }
}
