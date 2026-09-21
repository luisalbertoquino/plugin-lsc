( function () {
	'use strict';

	if ( typeof lscAccesibilidadData === 'undefined' ) {
		return;
	}

	var BLOCKS = lscAccesibilidadData.blocks || [];
	var CAMPUS = lscAccesibilidadData.campusVirtual || null;
	var HOVER_DELAY = 150;
	var DEFAULT_BOX_WIDTH = 100;

	var HAND_ICON_SVG =
		'<svg viewBox="0 0 24 24" aria-hidden="true">' +
		'<path d="M9 3a1 1 0 0 1 2 0v7h1V2a1 1 0 0 1 2 0v8h1V4a1 1 0 0 1 2 0v9.5l1.6-1.4a1.4 1.4 0 0 1 2 2L15 19.5c-1 1-2.3 1.5-3.6 1.5H10c-2 0-3.8-1-5-2.6L2.4 14a1.3 1.3 0 0 1 2-1.7L6 14V5a1 1 0 0 1 2 0v6h1V3z"/>' +
		'</svg>';

	function extractItemId( el ) {
		var match = el.className.match( /menu-item-(\d+)/ );
		if ( match ) {
			return match[1];
		}
		if ( el.id ) {
			match = el.id.match( /menu-item-(\d+)/ );
			if ( match ) {
				return match[1];
			}
		}
		return null;
	}

	/**
	 * Crea el <img>/<video> del contenido LSC. Si el archivo falla al
	 * cargar (borrado, red, formato no soportado), reemplaza el elemento
	 * por un aviso de texto en el mismo contenedor.
	 */
	function buildMediaElement( item, extraAttrs, onError ) {
		var el;

		function handleError() {
			if ( typeof onError === 'function' ) {
				onError();
			}
		}

		if ( 'mp4' === item.type ) {
			el = document.createElement( 'video' );
			el.src = item.url;
			el.muted = true;
			el.loop = true;
			el.playsInline = true;
			el.autoplay = true;
			el.addEventListener( 'error', handleError );
		} else {
			el = document.createElement( 'img' );
			el.src = item.url;
			el.alt = 'Interpretación en Lengua de Señas Colombiana';
			el.addEventListener( 'error', handleError );
		}

		if ( extraAttrs ) {
			Object.keys( extraAttrs ).forEach( function ( key ) {
				el.setAttribute( key, extraAttrs[ key ] );
			} );
		}

		return el;
	}

	function buildErrorNotice() {
		var notice = document.createElement( 'p' );
		notice.className = 'lsc-media-error';
		notice.textContent = 'Video no disponible';
		return notice;
	}

	/**
	 * Precarga en caché del navegador todos los archivos LSC (imágenes/GIF
	 * y videos) para que, al hacer hover o tocar el ícono, se muestren de
	 * inmediato en vez de aparecer primero el recuadro vacío mientras cargan.
	 */
	function preloadMedia( items ) {
		Object.keys( items ).forEach( function ( itemId ) {
			var item = items[ itemId ];
			if ( ! item || ! item.url ) {
				return;
			}

			if ( 'mp4' === item.type ) {
				var video = document.createElement( 'video' );
				video.preload = 'auto';
				video.muted = true;
				video.src = item.url;
			} else {
				var img = new Image();
				img.src = item.url;
			}
		} );
	}

	/**
	 * Renderiza el contenido LSC dentro de un contenedor, reemplazándolo
	 * por un aviso de error si el archivo no logra cargar.
	 */
	function renderMediaInto( container, item, extraAttrs ) {
		container.innerHTML = '';
		var media = buildMediaElement( item, extraAttrs, function () {
			container.innerHTML = '';
			container.appendChild( buildErrorNotice() );
		} );
		container.appendChild( media );
	}

	/* ---------- Escritorio: recuadro flotante en hover (compartido entre bloques) ---------- */

	function createDesktopHover() {
		var box = document.createElement( 'div' );
		box.className = 'lsc-hover-box';
		box.setAttribute( 'role', 'img' );
		box.setAttribute( 'aria-hidden', 'true' );
		document.body.appendChild( box );

		var showTimer = null;
		var hideTimer = null;

		function hide() {
			box.classList.remove( 'is-visible' );
			box.innerHTML = '';
		}

		function scheduleHide() {
			clearTimeout( showTimer );
			hideTimer = setTimeout( hide, HOVER_DELAY );
		}

		function show( link, item, config ) {
			config = config || {};
			var offsetX   = config.x || 0;
			var offsetY   = config.y || 0;
			var boxWidth  = config.width || DEFAULT_BOX_WIDTH;

			clearTimeout( hideTimer );
			showTimer = setTimeout( function () {
				renderMediaInto( box, item );
				box.classList.add( 'is-visible' );
				box.style.width = boxWidth + 'px';

				var rect = link.getBoundingClientRect();
				var gap = -10;
				var left = rect.left - boxWidth - gap + offsetX;

				if ( left < 12 ) {
					left = rect.left + offsetX;

					if ( left + boxWidth > window.innerWidth ) {
						left = window.innerWidth - boxWidth - 12;
					}
				}

				box.style.top = rect.bottom + 32 + offsetY + 'px';
				box.style.left = Math.max( 12, left ) + 'px';
			}, HOVER_DELAY );
		}

		function attach( link, item, config ) {
			link.addEventListener( 'mouseenter', function () {
				show( link, item, config );
			} );
			link.addEventListener( 'mouseleave', scheduleHide );
			link.addEventListener( 'focus', function () {
				show( link, item, config );
			} );
			link.addEventListener( 'blur', scheduleHide );
		}

		box.addEventListener( 'mouseenter', function () {
			clearTimeout( hideTimer );
		} );
		box.addEventListener( 'mouseleave', scheduleHide );

		return attach;
	}

	function initDesktopHoverForBlock( attach, menu, containerSelector, items, config ) {
		if ( ! menu ) {
			return;
		}

		var menuLinks = menu.querySelectorAll( containerSelector );

		menuLinks.forEach( function ( li ) {
			var itemId = extractItemId( li );
			if ( ! itemId || ! items[ itemId ] ) {
				return;
			}

			var link = li.querySelector( ':scope > a' );
			if ( ! link ) {
				return;
			}

			attach( link, items[ itemId ], config );
		} );
	}

	/* ---------- Móvil: ícono junto al enlace + modal ---------- */

	function createModal() {
		var overlay = document.createElement( 'div' );
		overlay.className = 'lsc-modal-overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );

		var modal = document.createElement( 'div' );
		modal.className = 'lsc-modal';

		var closeButton = document.createElement( 'button' );
		closeButton.type = 'button';
		closeButton.className = 'lsc-modal-close';
		closeButton.setAttribute( 'aria-label', 'Cerrar' );
		closeButton.innerHTML = '&times;';

		var mediaHolder = document.createElement( 'div' );
		mediaHolder.className = 'lsc-modal-media';

		modal.appendChild( closeButton );
		modal.appendChild( mediaHolder );
		overlay.appendChild( modal );
		document.body.appendChild( overlay );

		var lastFocused = null;

		function closeModal() {
			overlay.classList.remove( 'is-visible' );
			mediaHolder.innerHTML = '';
			if ( lastFocused ) {
				lastFocused.focus();
			}
		}

		function openModal( item, triggerEl ) {
			lastFocused = triggerEl;
			renderMediaInto( mediaHolder, item, { controls: 'controls' } );
			overlay.classList.add( 'is-visible' );
			closeButton.focus();
		}

		closeButton.addEventListener( 'click', closeModal );
		overlay.addEventListener( 'click', function ( event ) {
			if ( event.target === overlay ) {
				closeModal();
			}
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && overlay.classList.contains( 'is-visible' ) ) {
				closeModal();
			}
		} );

		return openModal;
	}

	function attachMobileIcon( li, link, item, openModal ) {
		var icon = document.createElement( 'span' );
		icon.className = 'lsc-mobile-icon';
		icon.setAttribute( 'role', 'button' );
		icon.setAttribute( 'tabindex', '0' );
		icon.setAttribute( 'aria-label', 'Ver interpretación en Lengua de Señas Colombiana' );
		icon.innerHTML = HAND_ICON_SVG;

		icon.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			openModal( item, icon );
		} );
		icon.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key || ' ' === event.key ) {
				event.preventDefault();
				event.stopPropagation();
				openModal( item, icon );
			}
		} );

		/*
		 * Se inserta justo antes del <ul class="dropdown-menu"> (si el ítem
		 * tiene submenú) para no interponerse entre el <a> y el <b class="caret">,
		 * que es la zona táctil que abre el submenú en los ítems de nivel 0.
		 * Si no hay submenú, simplemente queda al final del <li>.
		 */
		var submenu = li.querySelector( ':scope > .dropdown-menu' );
		if ( submenu ) {
			li.insertBefore( icon, submenu );
		} else {
			li.appendChild( icon );
		}
		li.classList.add( 'lsc-has-mobile-icon' );
	}

	function initMobileIconsForBlock( menu, containerSelector, items, openModal ) {
		if ( ! menu ) {
			return;
		}

		var menuLinks = menu.querySelectorAll( containerSelector );

		menuLinks.forEach( function ( li ) {
			var itemId = extractItemId( li );
			if ( ! itemId || ! items[ itemId ] ) {
				return;
			}

			var link = li.querySelector( ':scope > a' );
			if ( ! link ) {
				return;
			}

			attachMobileIcon( li, link, items[ itemId ], openModal );
		} );
	}

	/* ---------- Botón "Campus Virtual etR" (topbar) ---------- */

	function initCampusVirtualButton( desktopAttach, openModal ) {
		if ( ! CAMPUS || ! CAMPUS.url ) {
			return;
		}

		var item = { url: CAMPUS.url, type: CAMPUS.type };

		var button = document.querySelector( '.unnv-campus-btn' );
		if ( ! button ) {
			return;
		}

		if ( typeof desktopAttach === 'function' ) {
			desktopAttach( button, item, { x: CAMPUS.offsetX, y: CAMPUS.offsetY } );
		}

		var icon = document.createElement( 'span' );
		icon.className = 'lsc-mobile-icon lsc-mobile-icon-inline';
		icon.setAttribute( 'role', 'button' );
		icon.setAttribute( 'tabindex', '0' );
		icon.setAttribute( 'aria-label', 'Ver interpretación en Lengua de Señas Colombiana' );
		icon.innerHTML = HAND_ICON_SVG;

		icon.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			openModal( item, icon );
		} );
		icon.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key || ' ' === event.key ) {
				event.preventDefault();
				event.stopPropagation();
				openModal( item, icon );
			}
		} );

		button.insertAdjacentElement( 'afterend', icon );
	}

	function init() {
		/*
		 * Cubre tanto los <li> de nivel 0/1 con clase "level-X" (estructura
		 * estándar de los dropdowns) como los <li class="menu-item-XXXX">
		 * sin esa clase que WordPress genera dentro del megamenú de
		 * "Programas" (columnas Bootstrap con widgets de menú anidados).
		 */
		var levelSelector = 'li[class*="menu-item-"], li[id*="menu-item-"]';

		var desktopAttach = createDesktopHover();
		var openModal     = createModal();

		BLOCKS.forEach( function ( block ) {
			var desktopMenu = document.querySelector( block.selectorDesktop );
			var mobileMenu  = document.querySelector( block.selectorMobile );
			var config      = { x: block.offsetX, y: block.offsetY, width: block.boxWidth };

			preloadMedia( block.items );
			initDesktopHoverForBlock( desktopAttach, desktopMenu, levelSelector, block.items, config );
			initMobileIconsForBlock( mobileMenu, levelSelector, block.items, openModal );
		} );

		if ( CAMPUS && CAMPUS.url ) {
			preloadMedia( { campus: CAMPUS } );
		}

		initCampusVirtualButton( desktopAttach, openModal );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
