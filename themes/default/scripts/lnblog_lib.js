// lnblog_common.js - Shared function library for LnBlog pages.
// Copyright (C) 2007 Peter A. Geer <pageer@skepticats.com>
// This file is distributed under the terms of the GNU GPL version 2 or greater.

// Create a top-level lnblog object.  We use this as a poor-man's namespacing 
// mechanism.
var lnblog = {

    editor: {
        getMarkupMode: function () {
            // TODO: This should be injected by the text processors
            var modes = [
                'automarkup',
                'lbcode',
                'html',
                'markdown'
            ];

            var current_mode = $('#input_mode').val();
            return modes[parseInt(current_mode, 10)];
        },
        html: {
            insertLinkHandler: function (link_name, link) {
                var $body = $('#body');
                var content = '<a href="' + link + '">' + link_name + '</a>';
                lnblog.insertAtCursor($body[0], content);
            },
            insertImageHandler: function (link_name, link, full_link) {
                var $body = $('#body');
                var content = '<img src="' + link + '" alt="' + link_name +'" />';
                if (full_link) {
                    content = '<a href="' + full_link + '">' + content + '</a>';
                }
                lnblog.insertAtCursor($body[0], content);
            }
        },
        lbcode: {
            insertLinkHandler: function (link_name, link) {
                var $body = $('#body');
                var content = '[url=' + link + ']' + link_name + '[/url]';
                lnblog.insertAtCursor($body[0], content);
            },
            insertImageHandler: function (link_name, link, full_link) {
                var $body = $('#body');
                var content = '[img=' + link + ']' + link_name + '[/img]';
                if (full_link) {

                    content = '[url=' + full_link + ']' + content + '[/url]';
                }
                lnblog.insertAtCursor($body[0], content);
            }
        },
        markdown: {
            insertLinkHandler: function (link_name, link) {
                var $body = $('#body');
                var content = '[' + link_name + '](' + link + ')';
                lnblog.insertAtCursor($body[0], content);
            },
            insertImageHandler: function (link_name, link, full_link) {
                var $body = $('#body');
                var content = '![' + link_name + '](' + link + ')';
                if (full_link) {

                    content = '[' + content + '](' + full_link + ')';
                }
                lnblog.insertAtCursor($body[0], content);
            }
        },
        automarkup: {
            insertLinkHandler: function (link_name, link) {
                var $body = $('#body');
                lnblog.insertAtCursor($body[0], link);
            },
            insertImageHandler: undefined
        }
    },

    addEvent : function (obj, type, fn){
        if (obj.addEventListener){
            obj.addEventListener(type, fn, false);
        } else if (obj.attachEvent) {
            obj.attachEvent("on"+type, fn);
        } else {
            obj["on"+type] = obj["e"+type+fn];
        }
    },
    
    insertAtCursor: function (myField, myValue) {
        //IE support
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
        }
        //MOZILLA/NETSCAPE support
        else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos) + 
                myValue +
                myField.value.substring(endPos, myField.value.length);
        } else {
            myField.value += myValue;
        }
        myField.focus();
    },

    getSelection: function (myField) {
        var ret = false;
        //IE support
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            ret = sel.text;
        }
        //MOZILLA/NETSCAPE support
        else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            ret = myField.value.substring(startPos, endPos);
        } else {
            ret = false;
        }
        return ret;
    },
    
};

lnblog.addEvent(
    window, 'load', function() {
    if (typeof strings != 'undefined') {
        strings.get = function (string_id) {
            ret = this[string_id];
            for (var i = 1; i < arguments.length; i++) {
                ret = ret.replace('%'+i+'$s', arguments[i]);
            }
            return ret;
        };
    }
    }
);

$(document).ready(
    function () {
    $('#responsive-menu a').on(
        'click', function () {
        $('#sidebar').toggleClass('visible', true);
        $('#responsive-close').toggleClass('visible', true);
        $('#responsive-menu').toggleClass('hide', true);
        }
    );
    $('#responsive-close a').on(
        'click', function () {
        $('#sidebar').toggleClass('visible', false);
        $('#responsive-close').toggleClass('visible', false);
        $('#responsive-menu').toggleClass('hide', false);
        }
    );
    }
);
