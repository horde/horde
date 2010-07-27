/**
 * Provides the javascript class to create dynamic trees.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 */

var Horde_Tree = Class.create({

    initialize: function(opts)
    {
        this.opts = opts;

        if (this.opts.initTree) {
            this.renderTree(this.opts.initTree.nodes, this.opts.initTree.root_nodes, this.opts.initTree.is_static);
            this.opts.initTree = null;
        }

        $(this.opts.target).observe('click', this._onClick.bindAsEventListener(this));
    },

    renderTree: function(nodes, rootNodes, renderStatic)
    {
        this.nodes = nodes;
        this.rootNodes = rootNodes;
        this.renderStatic = renderStatic;
        this.dropline = [];
        this.node_pos = [];
        this.output = [];

        if (!this.opts.options.hideHeaders) {
            this.output.push(this._buildHeader());
        }

        this.rootNodes.each(this.buildTree.bind(this));

        $(this.opts.target).update(this.output.join(''));
        this._correctWidthForScrollbar();

        // If using alternating row shading, work out correct shade.
        if (this.opts.options.alternate) {
            this.stripe();
        }

        if (window.Horde_Tooltips) {
            window.Horde_ToolTips.attachBehavior();
        }
    },

    /**
     * Returns the HTML code for a header row, if necessary.
     *
     * @access private
     *
     * @return string  The HTML code of the header row or an empty string.
     */
    _buildHeader: function()
    {
        if (!this.opts.header.length) {
            return '';
        }

        var html = [ '<div>' ],
            i = 0;

        for (i = 0; i < this.opts.header.length; ++i) {
            html.push('<div');
            if (this.opts.header[i]['class']) {
                html.push(' class="' + this.opts.header[i]['class'] + '"');
            }

            html.push(' style="' + this.opts.floatDir);

            if (this.opts.header[i].width) {
                html.push('width:' + this.opts.header[i].width + ';');
            }
            if (this.opts.header[i].align) {
                html.push('text-align:' + this.opts.header[i].align + ';');
            }

            html.push('">' + (this.opts.header[i].html ? this.opts.header[i].html : '&nbsp;') + '</div>');
        }

        html.push('</div>');

        return html.join('');
    },

    /**
     * Recursive function to walk through the tree array and build
     * the output.
     */
    buildTree: function(nodeId)
    {
        var c, cId, numSubnodes, rowStyle;

        this.buildLine(nodeId);

        if (!Object.isUndefined(this.nodes[nodeId].children)) {
            numSubnodes = this.nodes[nodeId].children.length;
            if (numSubnodes > 0) {
                rowStyle = this.nodes[nodeId].expanded
                    ? 'display:block;'
                    : 'display:none;';
                this.output.push('<div id="nodeChildren_' + nodeId + '" style="' + rowStyle + '">');

                for (c = 0; c < numSubnodes; ++c) {
                    cId = this.nodes[nodeId].children[c];
                    this.node_pos[cId] = { count: numSubnodes, pos: c + 1 };
                    this.buildTree(cId);
                }

                this.output.push('</div>');
            }
        }
    },

    buildLine: function(nodeId)
    {
        var c, d, extra, i,
            column = 0,
            node = this.nodes[nodeId],
            o = this.output;

        o.push('<div class="treeRow');
        if (node['class']) {
            o.push(' ' + node['class']);
        }
        o.push('">');

        // If we have headers, track which logical "column" we're in for
        // any given cell of content.
        if (!Object.isUndefined(node.extra) &&
            !Object.isUndefined(node.extra[0])) {
            extra = node.extra[0];
            for (c = 0; c < extra.length; ++c) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column]['width']) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">' + extra[c] + '</div>');
                ++column;
            }

            for (d = c; d < this.opts.extraColsLeft; ++d) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column].width) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">&nbsp;</div>');
                ++column;
            }
        } else {
            for (c = 0; c < this.opts.extraColsLeft; ++c) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column].width) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">&nbsp;</div>');
                ++column;
            }
        }

        o.push('<div style="' + this.opts.floatDir);
        if (this.opts.header[column] && this.opts.header[column].width) {
            o.push('width:' + this.opts.header[column]['width'] + ';');
        }
        o.push('">');

        if (this.opts.options.multiline) {
            o.push('<table cellspacing="0"><tr><td>');
        }

        for (i = this.renderStatic ? 1 : 0; i < node.indent; ++i) {
            o.push('<img src="');
            if (this.dropline[i] && this.opts.options.lines) {
                o.push(this.opts.imgLine + '" alt="|');
            } else {
                o.push(this.opts.imgBlank + '" alt="');
            }
            o.push('&nbsp;&nbsp;&nbsp;" />');
        }

        o.push(this._setNodeToggle(nodeId));
        if (this.opts.options.multiline) {
            o.push('</td><td>');
        }
        o.push(this._setLabel(nodeId));

        if (this.opts.options.multiline) {
            o.push('</td></tr></table>');
        }

        o.push('</div>');
        ++column;

        if (!Object.isUndefined(node.extra) &&
            !Object.isUndefined(node.extra[1])) {
            extra = node.extra[1];

            for (c = 0; c < extra.length; ++c) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column].width) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">' + extra[c] + '</div>');
                ++column;
            }

            for (d = c; d < this.opts.extraColsRight; ++d) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column].width) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">&nbsp;</div>');
                ++column;
            }
        } else {
            for (c = 0; c < this.opts.extraColsRight; ++c) {
                o.push('<div style="' + this.opts.floatDir);
                if (this.opts.header[column] &&
                    this.opts.header[column].width) {
                    o.push('width:' + this.opts.header[column].width + ';');
                }
                o.push('">&nbsp;</div>');
                ++column;
            }
        }
        o.push('</div>');
    },

    _setLabel: function(nodeId)
    {
        var label = [],
            node = this.nodes[nodeId];

        if (node.url) {
            label.push('<span><a');

            if (node.urlclass) {
                label.push(' class="' + node.urlclass + '"');
            } else if (this.opts.options.urlclass) {
                label.push(' class="' + this.opts.options.urlclass + '"');
            }

            label.push(' href="' + node.url + '"');

            if (node.title) {
                label.push(' title="' + node.title + '"');
            }

            if (node.target) {
                label.push(' target="' + node.target + '"');
            } else if (this.opts.options.target) {
                label.push(' target="' + this.opts.options.target + '"');
            }

            if (node.onclick) {
                label.push(' onclick="' + node.onclick + '"');
            }

            label.push('>' + this._setNodeIcon(nodeId) + node.label + '</a></span>');
        } else {
            label.push('<span class="toggle">' + this._setNodeIcon(nodeId) + node.label + '</span>');
        }

        return label.join('');
    },

    _setNodeToggle: function(nodeId)
    {
        var img = [],
            node = this.nodes[nodeId],
            nodeToggle = this._getNodeToggle(nodeId);

        if (node.indent == '0' && !Object.isUndefined(node.children)) {
            // Top level with children.
            this.dropline[0] = false;
            if (this.renderStatic) {
                return '';
            }
        } else if (node.indent != '0' && Object.isUndefined(node.children)) {
            // Node no children.
            this.dropline[node.indent] = (this.node_pos[nodeId].pos < this.node_pos[nodeId].count);
        } else if (!Object.isUndefined(node.children)) {
            this.dropline[node.indent] = (this.node_pos[nodeId].pos < this.node_pos[nodeId].count);
        } else {
            // Top level node with no children.
            if (this.renderStatic) {
                return '';
            }
            this.dropline[0] = false;
        }

        img.push('<img class="treeToggle" id="nodeToggle_' + nodeId + '" src="' + nodeToggle[0] + '" ');
        if (nodeToggle[1]) {
            img.push('alt="' + nodeToggle[1] + '" ');
        }
        img.push('/>');

        return img.join('');
    },

    _getNodeToggle: function(nodeId)
    {
        var node = this.nodes[nodeId],
            nodeToggle = ['', ''];

        if (node.indent == '0' && !Object.isUndefined(node.children)) {
            // Top level with children.
            if (this.renderStatic) {
                return nodeToggle;
            } else if (!this.opts.options.lines) {
                nodeToggle[0] = this.opts.imgBlank;
                nodeToggle[1] = '&nbsp;&nbsp;&nbsp;'
            } else if (node.expanded) {
                nodeToggle[0] = this.opts.imgMinusOnly;
                nodeToggle[1] = '-';
            } else {
                nodeToggle[0] = this.opts.imgPlusOnly;
                nodeToggle[1] = '+';
            }
        } else if (node.indent != '0' && Object.isUndefined(node.children)) {
            // Node no children.
            if (this.node_pos[nodeId].pos < this.node_pos[nodeId].count) {
                // Not last node.
                if (this.opts.options.lines) {
                    nodeToggle[0] = this.opts.imgJoin;
                    nodeToggle[1] = '|-';
                } else {
                    nodeToggle[0] = this.opts.imgBlank;
                    nodeToggle[1] = '&nbsp;&nbsp;&nbsp;';
                }
            } else {
                // Last node.
                if (this.opts.options.lines) {
                    nodeToggle[0] = this.opts.imgJoinBottom;
                    nodeToggle[1] = '`-';
                } else {
                    nodeToggle[0] = this.opts.imgBlank;
                    nodeToggle[1] = '&nbsp;&nbsp;&nbsp;';
                }
            }
        } else if (!Object.isUndefined(node.children)) {
            // Node with children.
            if (this.node_pos[nodeId].pos < this.node_pos[nodeId].count) {
                // Not last node.
                if (!this.opts.options.lines) {
                    nodeToggle[0] = this.opts.imgBlank;
                    nodeToggle[1] = '&nbsp;&nbsp;&nbsp;';
                } else if (this.renderStatic) {
                    nodeToggle[0] = this.opts.imgJoin;
                    nodeToggle[1] = '|-';
                } else if (node.expanded) {
                    nodeToggle[0] = this.opts.imgMinus;
                    nodeToggle[1] = '-';
                } else {
                    nodeToggle[0] = this.opts.imgPlus;
                    nodeToggle[1] = '+';
                }
            } else {
                // Last node.
                if (!this.opts.options.lines) {
                    nodeToggle[0] = this.opts.imgBlank;
                    nodeToggle[1] = '&nbsp;';
                } else if (this.renderStatic) {
                    nodeToggle[0] = this.opts.imgJoinBottom;
                    nodeToggle[1] = '`-';
                } else if (node.expanded) {
                    nodeToggle[0] = this.opts.imgMinusBottom;
                    nodeToggle[1] = '-';
                } else {
                    nodeToggle[0] = this.opts.imgPlusBottom;
                    nodeToggle[1] = '+';
                }
            }
        } else {
            // Top level node with no children.
            if (this.renderStatic) {
                return nodeToggle;
            }
            if (this.opts.options.lines) {
                nodeToggle[0] = this.opts.imgNullOnly;
                nodeToggle[1] = '&nbsp;&nbsp;';
            } else {
                nodeToggle[0] = this.opts.imgBlank;
                nodeToggle[1] = '&nbsp;&nbsp;&nbsp;';
            }
        }

        return nodeToggle;
    },

    _setNodeIcon: function(nodeId)
    {
        var img = [],
            node = this.nodes[nodeId];

        img.push('<img class="treeIcon" id="nodeIcon_' + nodeId + '" src="');

        // Image directory.
        if (!Object.isUndefined(node.icondir) && node.icondir) {
            img.push(node.icondir + '/');
        }

        // Image.
        if (!Object.isUndefined(node.icon)) {
            // Node has a user defined icon.
            if (!node.icon) {
                return '';
            }

            img.push(!Object.isUndefined(node.iconopen) && node.expanded ? node.iconopen : node.icon);
        } else {
            // Use standard icon set.
            if (!Object.isUndefined(node.children)) {
                // Node with children.
                img.push(node.expanded ? this.opts.imgFolderOpen : this.opts.imgFolder);
            } else {
                // Node, no children.
                img.push(this.opts.imgLeaf);
            }
        }

        img.push('"');

        if (!Object.isUndefined(node.iconalt)) {
            img.push(' alt="' + node.iconalt + '"');
        }

        img.push(' />');

        return img.join('');
    },

    toggle: function(nodeId)
    {
        var icon, nodeToggle, toggle, children,
            node = this.nodes[nodeId],
            src = [];

        node.expanded = !node.expanded;
        if (children = $('nodeChildren_' + nodeId)) {
            children.setStyle({ display: node.expanded ? 'block' : 'none' });
        }

        // Toggle the node's icon if it has separate open and closed
        // icons.
        if (icon = $('nodeIcon_' + nodeId)) {
            // Image directory.
            if (!Object.isUndefined(node.icondir) && node.icondir) {
                src.push(node.icondir + '/');
            }

            // Image.
            if (!Object.isUndefined(node.icon)) {
                src.push((node.expanded && node.iconopen) ? node.iconopen : node.icon);
            } else {
                // Use standard icon set.
                src.push(node.expanded ? this.opts.imgFolderOpen : this.opts.imgFolder);
            }

            icon.src = src.join('');
        }

        // If using alternating row shading, work out correct shade.
        if (this.opts.options.alternate) {
            this.stripe();
        }

        nodeToggle = this._getNodeToggle(nodeId);
        if (toggle = $('nodeToggle_' + nodeId)) {
            toggle.src = nodeToggle[0];
            toggle.alt = nodeToggle[1];
        }

        this.saveState(nodeId, node.expanded)
    },

    stripe: function()
    {
        var classes = [ 'rowEven', 'rowOdd' ],
            i = 0;

        $(this.opts.target).select('DIV.treeRow').each(function(r) {
            classes.each(r.removeClassName.bind(r));
            if (r.clientHeight) {
                r.addClassName(classes[++i % 2]);
            }
        });
    },

    saveState: function(nodeId, expanded)
    {
        var newCookie = '',
            newNodes = [],
            oldCookie = this._getCookie(this.opts.target + '_expanded');

        if (expanded) {
            // Expand requested so add to cookie.
            newCookie = (oldCookie ? oldCookie + ',' : '') + nodeId;
        } else {
            // Collapse requested so remove from cookie.
            oldCookie.split(',').each(function(n) {
                if (n != nodeId) {
                    newNodes[newNodes.length] = n
                }
            });
            newCookie = newNodes.join(',');
        }

        this._setCookie(this.opts.target + '_expanded', newCookie);
    },

    _getCookie: function(name)
    {
        var end,
            dc = document.cookie,
            prefix = name + '=exp',
            begin = dc.indexOf('; ' + prefix);

        if (begin == -1) {
            begin = dc.indexOf(prefix);
            if (begin != 0) {
                return '';
            }
        } else {
            begin += 2;
        }

        end = document.cookie.indexOf(';', begin);
        if (end == -1) {
            end = dc.length;
        }

        return unescape(dc.substring(begin + prefix.length, end));
    },

    _setCookie: function(name, value)
    {
        document.cookie = name + '=exp' + escape(value) + ';DOMAIN=' + this.opts.cookieDomain + ';PATH=' + this.opts.cookiePath + ';';
    },

    _correctWidthForScrollbar: function()
    {
        if (this.opts.scrollbar_in_way) {
            /* Correct for frame scrollbar in IE by determining if a scrollbar
             * is present, and if not readjusting the marginRight property to
             * 0. See http://www.xs4all.nl/~ppk/js/doctypes.html for why this
             * works */
            if (document.documentElement.clientHeight == document.documentElement.offsetHeight) {
                // no scrollbar present, take away extra margin
                document.body.style.marginRight = 0;
            } else {
                document.body.style.marginRight = '15px';
            }
        }
    },

    _onClick: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id');

        if (elt.hasClassName('treeIcon')) {
            elt = elt.up().previous();
        } else if (elt.hasClassName('toggle')) {
            elt = elt.previous();
        }

        id = elt.readAttribute('id');
        if (id && id.startsWith('nodeToggle_')) {
            this.toggle(id.substr(11));
            e.stop();
        }
    }

});
