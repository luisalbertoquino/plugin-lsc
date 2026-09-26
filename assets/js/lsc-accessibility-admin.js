(function ( $ ) {
	'use strict';

	function getExtension( url ) {
		var clean = url.split( '?' )[0];
		var parts = clean.split( '.' );
		return parts.length > 1 ? parts.pop().toLowerCase() : '';
	}

	function updatePreview( $row, url, type ) {
		var $preview = $row.find( '.lsc-preview' );

		$preview.empty();

		if ( ! url ) {
			$( '<span class="lsc-preview-empty">Sin contenido asignado</span>' ).appendTo( $preview );
			return;
		}

		if ( 'mp4' === type ) {
			$( '<video>', { muted: true, loop: true, playsinline: true, width: 100, src: url } ).appendTo( $preview );
		} else {
			$( '<img>', { src: url, alt: '', width: 100 } ).appendTo( $preview );
		}
	}

	function getMaxFileSize() {
		if ( typeof lscAccesibilidadAdmin !== 'undefined' && lscAccesibilidadAdmin.maxFileSize ) {
			return lscAccesibilidadAdmin.maxFileSize;
		}
		return 3145728; // 3 MB por defecto si no llegó la config del servidor.
	}

	function getMaxFileSizeLabel() {
		if ( typeof lscAccesibilidadAdmin !== 'undefined' && lscAccesibilidadAdmin.maxFileSizeLabel ) {
			return lscAccesibilidadAdmin.maxFileSizeLabel;
		}
		return '3 MB';
	}

	$( function () {
		var frame;

		/*
		 * Delegados en document (no atados directamente a cada botón):
		 * la sección de ítems del formulario de bloques se reemplaza por
		 * AJAX al cambiar de menú (ver más abajo), así que los botones
		 * "Subir"/"Quitar" de las filas insertadas después también deben
		 * quedar funcionando sin tener que re-enganchar nada.
		 */
		$( document ).on( 'click', '.lsc-upload-button', function ( event ) {
			event.preventDefault();

			var $button = $( this );
			var $row    = $button.closest( 'tr' );

			frame = wp.media( {
				title: 'Selecciona un GIF o Video',
				library: {
					type: [ 'image/gif', 'video/mp4' ],
				},
				button: {
					text: 'Usar este archivo',
				},
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();

				if ( attachment.filesizeInBytes && attachment.filesizeInBytes > getMaxFileSize() ) {
					window.alert(
						'El archivo pesa ' + ( attachment.filesizeHumanReadable || '' ) +
						' y supera el máximo permitido de ' + getMaxFileSizeLabel() +
						'. Comprime el archivo e inténtalo de nuevo.'
					);
					return;
				}

				var extension = getExtension( attachment.url );
				var type      = 'mp4' === extension ? 'mp4' : 'gif';

				$row.find( '.lsc-url-field' ).val( attachment.url );
				$row.find( '.lsc-type-field' ).val( type );
				$row.find( '.lsc-attachment-field' ).val( attachment.id );
				$row.find( '.lsc-remove-button' ).prop( 'disabled', false );

				updatePreview( $row, attachment.url, type );
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.lsc-remove-button', function ( event ) {
			event.preventDefault();

			var $button = $( this );
			var $row    = $button.closest( 'tr' );

			$row.find( '.lsc-url-field' ).val( '' );
			$row.find( '.lsc-type-field' ).val( '' );
			$row.find( '.lsc-attachment-field' ).val( '' );
			$button.prop( 'disabled', true );

			updatePreview( $row, '', '' );
		} );

		/*
		 * Formulario de bloques: al cambiar el menú de origen, se cargan
		 * por AJAX los ítems principales de ese menú dentro de
		 * #lsc-items-section, sin recargar el resto del formulario (así
		 * no se pierde el nombre, tamaño, posición, etc. ya llenados).
		 */
		var $menuSelect   = $( '#lsc-menu' );
		var $itemsSection = $( '#lsc-items-section' );

		if ( $menuSelect.length && $itemsSection.length && typeof lscAccesibilidadAdmin !== 'undefined' ) {
			$menuSelect.on( 'change', function () {
				var menuId = $menuSelect.val();
				var $spinner = $menuSelect.closest( 'td' ).find( '.lsc-menu-spinner' );
				var $form = $menuSelect.closest( 'form' );

				if ( ! menuId ) {
					$itemsSection.empty();
					return;
				}

				$spinner.addClass( 'is-active' );
				$menuSelect.prop( 'disabled', true );

				$.post( lscAccesibilidadAdmin.ajaxUrl, {
					action: 'lsc_get_menu_items',
					nonce: lscAccesibilidadAdmin.menuItemsNonce,
					menu_id: menuId,
					id: $form.find( '[name="id"]' ).val() || 0,
				} ).done( function ( response ) {
					if ( response && response.success ) {
						$itemsSection.html( response.data.html );
					} else {
						window.alert( 'No se pudieron cargar los ítems de este menú. Intenta de nuevo.' );
					}
				} ).fail( function () {
					window.alert( 'No se pudieron cargar los ítems de este menú. Intenta de nuevo.' );
				} ).always( function () {
					$spinner.removeClass( 'is-active' );
					$menuSelect.prop( 'disabled', false );
				} );
			} );
		}
	} );
})( jQuery );
