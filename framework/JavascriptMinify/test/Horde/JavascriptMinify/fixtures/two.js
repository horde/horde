/* Helper methods for setting/getting element text without mucking
 * around with multiple TextNodes. */
Element.addMethods({
    setText: function(element, text)
    {
        var t = 0;
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                if (t++) {
                    Element.remove(node);
                } else {
                    node.nodeValue = text;
                }
            }
        });

        if (!t) {
            $(element).insert(text);
        }

        return element;
    },

    getText: function(element, recursive)
    {
        var text = '';
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                text += node.nodeValue;
            } else if (recursive && node.hasChildNodes()) {
                text += $(node).getText(true);
            }
        });
        return text;
    }
});

/* IE 11 fix:
 * https://prototype.lighthouseapp.com/projects/8886/tickets/3508-ie11-support
 */
Prototype.Browser = (function(res) {
    if (navigator.userAgent.include('Trident')) {
        res.Gecko = false;
        res.IE = true;
    }
    return res;
})(Prototype.Browser);
