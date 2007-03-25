/* $Id$ */

/**
 * lrtrim
 */
function lrtrim(value) {
	var l, r;
	for (l = 0;                l < value.length && value.charCodeAt(l) == 32; l++);
	for (r = value.length - 1; r > l            && value.charCodeAt(r) == 32; r--);
	return value.substring(l, r + 1);
}
