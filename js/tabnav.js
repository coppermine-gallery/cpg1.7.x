/**
 * Coppermine Photo Gallery
 *
 * v1.0 originally written by Gregory Demar
 *
 * @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
 * @license	   GNU General Public License version 3 or later; see LICENSE
 *
 * js/tabnav.js
 * @since  1.7.03
 */

var kt_nav = (function () {
	let elm,
		prev,
		next,
		totl,
		inSwipe = false,
		startx = 0,
		tlpx = 0;

	const _next = () => {
		if (next) window.location = 'thumbnails.php?album=lastup&cat=0&page='+next;
	}
	const _prev = () => {
		if (prev) window.location = 'thumbnails.php?album=lastup&cat=0&page='+prev;
	}
	const _first = (evt) => {
		if (prev) {
			evt.preventDefault();
			window.location = 'thumbnails.php?album=lastup&cat=0&page=1';
		}
	}
	const _last = (evt) => {
		if (next) {
			evt.preventDefault();
			window.location = 'thumbnails.php?album=lastup&cat=0&page='+totl;
		}
	}

	const tDown = (evt) => {
		if (evt.target == elm) {
		startx = evt.screenX;
		inSwipe = true;
		console.log(evt);
		}
	};
	const tMove = (evt) => {
		if (inSwipe) {
			console.log(evt);
	//		elm.style.marginLeft = (evt.screenX - startx + tlpx)+'px';
		}
	};
	const tUp = (evt) => {
		if (!inSwipe) return;
		inSwipe = false;
	//	tlpx = T.offsetLeft;
	//	elm.style.marginLeft = 0;
		console.log(evt);
		let ex = evt.screenX;
		if (Math.abs(ex - startx) > 100) {
			if (ex > startx) {
				_prev();
			} else {
				_next();
			}
		}
	};

	const keyd = (evt) => {
		console.log(evt);
		switch (evt.code) {
			case 'ArrowRight':
				_next();
				break;
			case 'ArrowLeft':
				_prev();
				break;
			case 'ArrowUp':
				_first(evt);
				break;
			case 'ArrowDown':
				_last(evt);
				break;
		}
	};

	return {
		init: (sel, prevp, nextp, totlp) => {
			elm = document.querySelector(sel);
			prev = prevp;
			next = nextp;
			totl = totlp;
			console.log(elm,prev,next,totl);
			document.addEventListener('keydown', keyd, true);
			if (window.PointerEvent) {
				elm.addEventListener( 'pointerdown', tDown, false);
				elm.addEventListener( 'pointermove', tMove, false);
				elm.addEventListener( 'pointerup', tUp, false);
			//	elm.addEventListener( 'pointerout', tUp, false);
			//	elm.addEventListener( 'pointercancel', tUp, false);
			//	elm.addEventListener( 'pointerleave', tUp, false);
			} else {
				elm.addEventListener('touchstart', tDown , false);
				elm.addEventListener('touchend',  tUp, false);
			}
			elm.focus();
		}
	};

})();

var kt_img_nav = (function () {
	let bar,
		elm,
		astart,
		aprev,
		anext,
		aend,
		inSwipe = false,
		startx = 0,
		tlpx = 0;

	const _next = () => {
		anext.click();
	}
	const _prev = () => {
		aprev.click();
	}
	const _first = (evt) => {
		astart.click();
	}
	const _last = (evt) => {
		aend.click();
	}

	const tDown = (evt) => {	console.log(evt.target);
		if (evt.target == elm) {
		startx = evt.screenX;
		inSwipe = true;
		console.log(evt);
		}
	};
	const tMove = (evt) => {
		if (inSwipe) {
			console.log(evt);
	//		elm.style.marginLeft = (evt.screenX - startx + tlpx)+'px';
		}
	};
	const tUp = (evt) => {
		if (!inSwipe) return;
		inSwipe = false;
	//	tlpx = T.offsetLeft;
	//	elm.style.marginLeft = 0;
		console.log(evt);
		let ex = evt.screenX;
		if (Math.abs(ex - startx) > 100) {
			if (ex > startx) {
				_prev();
			} else {
				_next();
			}
		}
	};

	const keyd = (evt) => {
		console.log(evt);
		switch (evt.code) {
			case 'ArrowRight':
				_next();
				break;
			case 'ArrowLeft':
				_prev();
				break;
			case 'ArrowUp':
				_first(evt);
				break;
			case 'ArrowDown':
				_last(evt);
				break;
		}
	};

	return {
		init: (nav, sel) => {
			bar = document.querySelector(nav);
			elm = document.querySelector(sel);
			astart = bar.querySelector('.navmenu.imgstart a');
			aprev = bar.querySelector('.navmenu.imgprev a');
			anext = bar.querySelector('.navmenu.imgnext a');
			aend = bar.querySelector('.navmenu.imgend a');
			console.log(bar,elm,astart,aprev,anext,aend);
			document.addEventListener('keydown', keyd, true);
			if (window.PointerEvent) {
				elm.addEventListener( 'pointerdown', tDown, false);
				elm.addEventListener( 'pointermove', tMove, false);
				elm.addEventListener( 'pointerup', tUp, false);
			//	elm.addEventListener( 'pointerout', tUp, false);
			//	elm.addEventListener( 'pointercancel', tUp, false);
			//	elm.addEventListener( 'pointerleave', tUp, false);
			} else {
				elm.addEventListener('touchstart', tDown , false);
				elm.addEventListener('touchend',  tUp, false);
			}
			elm.focus();
		}
	};

})();
