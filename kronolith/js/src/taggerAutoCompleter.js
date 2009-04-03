var KronolithTagger = Class.create({
        initialize: function(params)
        {
            this.p = params;

            trigger = $(this.p.trigger);
            trigger.observe('keydown', this._onKeyDown.bindAsEventListener(this));

            // Bind this object to the trigger so we can call it's methods
            // from client code.
            trigger.kronolithTagger = this;

            // Make sure the right dom elements are styled correctly.
            $(this.p.container).addClassName('kronolithACListItem kronolithTagACContainer');

            // Make sure the p.tags element is hidden
            if (!this.p.debug) {
                $(this.p.tags).hide();
            }

            // Set the updateElement callback
            this.p.params.updateElement = this._updateElement.bind(this);

            // Look for clicks on the box to simulate clicking in an input box
            $(this.p.box).observe('click', function() {$(this.p.trigger).focus()}.bindAsEventListener(this));

            // Create the underlaying Autocompleter
            new Ajax.Autocompleter(params.trigger, params.resultsId, params.uri, params.params);

            // Prepopulate the tags and the container elements?
            if (this.p.existing) {
                this.init(this.p.existing);
            }

        },

        init: function(existing)
        {
            // TODO: Resize the trigger field to fill the current line?
            // Clear any existing values
            if (this.p.selectedTags.length) {
                $('kronolithTagACBox').select('li.kronolithTagACListItem').each(function(item) {this.removeTagNode(item) }.bind(this));
            }

            // Add any initial values
            if (typeof existing != 'undefined' && existing.length) {
                for (var i = 0, l = existing.length; i < l; i++) {
                    this.addNewTagNode(existing[i]);
                }
            }

        },

        _onKeyDown: function(e)
        {
            // Check for a comma
            if (e.keyCode == 188) {
                //Strip off leading commas
                value = $F(this.p.trigger).replace(/^,/, '');
                if (value.length) {
                    if (value.match(/^[^"]?"[^"]+$/)) {
                        // Unclosed quote
                        return;
                    }
                    this.addNewTagNode(value);
                }
                e.stop();
            }
        },

        // Used as the updateElement callback.
        _updateElement: function(item)
        {
            var value = item.collectTextNodesIgnoreClass('informal');
            this.addNewTagNode(value);
        },

        addNewTagNode: function(value)
        {
            // Don't add if it's already present.
            for (var x = 0, len = this.p.selectedTags.length; x < len; x++) {
                if (this.p.selectedTags[x] == value) {
                    return;
                }
            }

            var newTag = new Element('li', {class: 'kronolithACListItem kronolithTagACListItem'}).update(value);
            var x = new Element('img', {class: 'kronolithTagACRemove', src:this.p.URI_IMG_HORDE + "/delete-small.png"});
            x.observe('click', this._removeTagHandler.bindAsEventListener(this));
            newTag.insert(x);
            $(this.p.container).insert({before: newTag});
            $(this.p.trigger).value = '';

            // Add to hidden input field.
            if ($(this.p.tags).value) {
                $(this.p.tags).value = $(this.p.tags).value + ', ' + value;
            } else {
                $(this.p.tags).value = value;
            }

            // ...and keep the selectedTags array up to date.
            this.p.selectedTags.push(value);
        },

        removeTagNode: function(item)
        {
            var value = item.collectTextNodesIgnoreClass('informal');
            for (var x = 0, len = this.p.selectedTags.length; x < len; x++) {
                if (this.p.selectedTags[x] == value) {
                    this.p.selectedTags.splice(x, 1);
                }
            }
            item.remove();
        },

        _removeTagHandler: function(e)
        {
            item = Event.element(e).up();
            this.removeTagNode(item);
            $(this.p.tags).value = this.p.selectedTags.join(',');
        }
});