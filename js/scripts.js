/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2021 Coppermine Dev Team
 * @license	   GNU General Public License version 3 or later; see LICENSE
 *
 * js/scripts.js
 * @since  1.6.11
 */

// Object to hold javascript keyCodes for various keys
var KEY_CODES = {
	TAB : 9,
	ENTER : 13
};

var GB_ANIMATION = true;

$(function() {
	$(".cpg_zebra tr:even td:not(.tableh1)").addClass("tableb");
	$(".cpg_zebra tr:odd td:not(.tableh1)").addClass("tableb tableb_alternate");
});

function MM_openBrWindow(theURL,winName,features) { //v2.0
	window.open(theURL,winName,features);
}

function writeCookie(name, data, noDays) {
	if (js_vars.cookies_allowed == true) {
		var cookieStr = name + "="+ data;
		if (writeCookie.arguments.length > 2){
			cookieStr += "; expires=" + getCookieExpireDate(noDays);
		}
		document.cookie = cookieStr;
	}
}

function readCookie(cookieName) {
	var searchName = cookieName + "=";
	var cookies = document.cookie;
	var start = cookies.indexOf(cookieName);
	if (start == -1){ // cookie not found
		return "";
	}
	start += searchName.length; //start of the cookie data
	var end = cookies.indexOf(";", start);
	if (end == -1){
		end = cookies.length;
	}
	return cookies.substring(start, end);
}

function blocking(nr, cookie, vis_state) {
	display = ($("#" + nr).css('display') == 'none') ? vis_state : 'none';
	if (cookie != ''){
		writeCookie(nr, display);
	}
	$('#' + nr).css('display', display);
}

function show_section(e) {
	$('#' + e).toggle();
}

function expand() {
	$("table[id^='section']").show();
}

function hideall() {
	$("table[id^='section']").hide();
}

function selectAll(form_name) {
	$('#' + form_name).data('boxes_checked', $('#' + form_name).data('boxes_checked') ? false : true);
	$('#' + form_name + ' input:checkbox').each(function(){
		this.checked = $('#' + form_name).data('boxes_checked');
	});
}

function redirect(url) {
	window.location=url;
}

// Function used to not allow user to enter default username as username for comment
function notDefaultUsername(f, defaultUsername, defaultUsernameMessage) {
	 // If username for comment is default username then display error message and return false
	if (f.msg_author.value == defaultUsername || f.msg_author.value == '') {
		alert(defaultUsernameMessage);
		return false;
	}
	// By default return true
	return true;
}

function HighlightAll(theField) {
	var tempval=eval("document."+theField);
	tempval.focus();
	tempval.select();
}

var onloads = [];

function addonload(func){
	onloads.push(func);
}

/**
 * sprintf() for JavaScript v.0.4
 *
 * Copyright (c) 2007 Alexandru Marasteanu <http://alexei.417.ro/>
 * Thanks to David Baird (unit test and patch).
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 */

function str_repeat(i, m) { for (var o = []; m > 0; o[--m] = i); return(o.join('')); }

function sprintf () {
	var i = 0, a, f = arguments[i++], o = [], m, p, c, x;
	while (f) {
		if (m = /^[^\x25]+/.exec(f)) o.push(m[0]);
		else if (m = /^\x25{2}/.exec(f)) o.push('%');
		else if (m = /^\x25(?:(\d+)\$)?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(f)) {
			if (((a = arguments[m[1] || i++]) == null) || (a == undefined)) throw("Too few arguments.");
			if (/[^s]/.test(m[7]) && (typeof(a) != 'number'))
				throw("Expecting number but found " + typeof(a));
			switch (m[7]) {
				case 'b': a = a.toString(2); break;
				case 'c': a = String.fromCharCode(a); break;
				case 'd': a = parseInt(a); break;
				case 'e': a = m[6] ? a.toExponential(m[6]) : a.toExponential(); break;
				case 'f': a = m[6] ? parseFloat(a).toFixed(m[6]) : parseFloat(a); break;
				case 'o': a = a.toString(8); break;
				case 's': a = ((a = String(a)) && m[6] ? a.substring(0, m[6]) : a); break;
				case 'u': a = Math.abs(a); break;
				case 'x': a = a.toString(16); break;
				case 'X': a = a.toString(16).toUpperCase(); break;
			}
			a = (/[def]/.test(m[7]) && m[2] && a > 0 ? '+' + a : a);
			c = m[3] ? m[3] == '0' ? '0' : m[3].charAt(1) : ' ';
			x = m[5] - String(a).length;
			p = m[5] ? str_repeat(c, x) : '';
			o.push(m[4] ? a + p : p + a);
		}
		else throw ("Huh ?!");
		f = f.substring(m[0].length);
	}
	return o.join('');
}
// end function sprintf

// PHP equivalent of stript_tags.
// Source: http://phpjs.org
function strip_tags (str, allowed_tags) {
	// Strips HTML and PHP tags from a string
	//
	// version: 908.406
	// discuss at: http://phpjs.org/functions/strip_tags
	var key = '', allowed = false;
	var matches = [];
	var allowed_array = [];
	var allowed_tag = '';
	var i = 0;
	var k = '';
	var html = '';
	var replacer = function (search, replace, str) {
		return str.split(search).join(replace);
	};
	// Build allowes tags associative array
	if (allowed_tags) {
		allowed_array = allowed_tags.match(/([a-zA-Z0-9]+)/gi);
	}
	str += '';
	// Match tags
	matches = str.match(/(<\/?[\S][^>]*>)/gi);
	// Go through all HTML tags
	for (key in matches) {
		if (isNaN(key)) {
			// IE7 Hack
			continue;
		}
		// Save HTML tag
		html = matches[key].toString();
		// Is tag not in allowed list? Remove from str!
		allowed = false;
		// Go through all allowed tags
		for (k in allowed_array) {
			// Init
			allowed_tag = allowed_array[k];
			i = -1;
			if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+'>');}
			if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+' ');}
			if (i != 0) { i = html.toLowerCase().indexOf('</'+allowed_tag)	 ;}

			// Determine
			if (i == 0) {
				allowed = true;
				break;
			}
		}
		if (!allowed) {
			str = replacer(html, "", str); // Custom replace. No regexing
		}
	}
	return str;
}

// Function to strip all html from given string. It replaces <br> with \n
// This function is generally used to show error messages in js alert
function strip_html(content) {
	// We will first replace <br /> with \n
	content = content.replace('<br>', '\n');
	content = content.replace('<br />', '\n');
	return strip_tags(content);
}

//
function str_replace (search, replace, subject, count) {
	// Replaces all occurrences of search in haystack with replace
	//
	// version: 908.406
	// discuss at: http://phpjs.org/functions/str_replace
	var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
			f = [].concat(search),
			r = [].concat(replace),
			s = subject,
			ra = r instanceof Array, sa = s instanceof Array;
	s = [].concat(s);
	if (count) {
		this.window[count] = 0;
	}

	for (i=0, sl=s.length; i < sl; i++) {
		if (s[i] === '') { continue; }
		for (j=0, fl=f.length; j < fl; j++) {
			temp = s[i]+'';
			repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
			s[i] = (temp).split(f[j]).join(repl);
			if (count && s[i] !== temp) {
				this.window[count] += (temp.length-s[i].length)/f[j].length;
			}
		}
	}
	return sa ? s : s[0];
}

function toggle_resp_menu (evt) {
	let t = evt.target;
	if (t.nodeName == 'path') t = t.parentElement;
	let mnul = t.parentElement.nextElementSibling;
	mnul.classList.toggle('stashed');
}


$(document).ready(function() {
	for (var func in onloads) {
		eval(onloads[func]);
	}

	//hide all elements with class detail_body
	$(".detail_body").hide();
	//toggle the component with class detail_body
	$(".detail_head_collapsed").click(function()
	{
		$(this).toggleClass("detail_head_expanded").next(".detail_body").slideToggle(600);
	});
	$(".detail_expand_all").click(function()
	{
		$(".detail_body").slideDown(1200);
		$(".detail_head_collapsed").addClass("detail_head_expanded");
		$(".detail_expand_all").hide();
		$(".detail_collapse_all").show();

	});
	$(".detail_collapse_all").click(function()
	{
		$(".detail_body").slideUp(1200);
		$(".detail_head_collapsed").removeClass("detail_head_expanded");
		$(".detail_expand_all").show();
		$(".detail_collapse_all").hide();

	});
	$(".detail_toggle_all").click(function()
	{
		$(".detail_body").slideToggle(600);
		$(".detail_head_collapsed").toggleClass("detail_head_expanded");
	});

	// Greybox plugin initialization for the help system
	$("a.greybox").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,470,600);
		return false;
	});
	$("a.greyboxfull").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,700,800);
		return false;
	});
	$('.elastic').elastic();

	//hide all elements with class noscript
	$(".noscript").hide();
});
