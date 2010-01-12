// InPlaceEditor extension based somewhat on an example given in the
// scriptaculous wiki
Ajax.InPlaceEditor.prototype.__initialize = Ajax.InPlaceEditor.prototype.initialize;
Ajax.InPlaceEditor.prototype.__getText = Ajax.InPlaceEditor.prototype.getText;
Object.extend(Ajax.InPlaceEditor.prototype, {
    initialize: function(element, url, options) {
        this.__initialize(element, url, options);
        this.setOptions(options);
        // Remove this line to stop from auto-showing the
        // empty caption text on page load.
        this.checkEmpty();
    },

    setOptions: function(options) {
        this.options = Object.extend(Object.extend(this.options, {
            emptyClassName: 'inplaceeditor-empty'
        }),options||{});
    },

    checkEmpty: function() {
        if (this.element.innerHTML.length == 0) {
            emptyNode = new Element('span', {className: this.options.emptyClassName}).update(this.options.emptyText);
            this.element.appendChild(emptyNode);
        }
    },

    getText: function() {
        $(this.element).select('.' + this.options.emptyClassName).each(function(child) {
            this.element.removeChild(child);
        }.bind(this));
        return this.__getText();
    }
});

function tileExit(ipe, e)
{
    ipe.checkEmpty();
}
