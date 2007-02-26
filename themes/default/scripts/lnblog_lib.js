// lnblog_common.js - Shared function library for LnBlog pages.
// Copyright (C) 2007 Peter A. Geer <pageer@skepticats.com>
// This file is distributed under the terms of the GNU GPL version 2 or greater.

// Create a top-level lnblog object.  We use this as a poor-man's namespacing 
// mechanism.
var lnblog = {

	addEvent : function (obj, type, fn){
		if (obj.addEventListener){
			obj.addEventListener(type, fn, false);
		} else if (obj.attachEvent) {
			obj.attachEvent("on"+type, fn);
		} else {
			obj["on"+type] = obj["e"+type+fn];
		}
	},
	
	createRequestObject: function () {
		var request;
		var browser = navigator.appName;
		if (browser == "Microsoft Internet Explorer") {
			request = new ActiveXObject("Microsoft.XMLHTTP");
		} else {
			request = new XMLHttpRequest();
		}
		return request;
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
	
	deserializeJSON : function (data) {
		return eval("("+data+")");
	}

};