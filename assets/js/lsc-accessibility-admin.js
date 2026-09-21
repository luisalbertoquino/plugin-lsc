(function ( $ ) {
	'use strict';

	function getExtension( url ) {
		var clean = url.split( '?' )[0];
		var parts = clean.split( '.' );
		return parts.length > 1 ? parts.pop().toLowerCase() : '';
	}

	function updatePreview( $row, url, type ) {
		var $preview = $row.find( '.lsc-preview' );

		if ( ! url ) {
			$preview.html( '<span class="lsc-preview-empty">Sin contenido asignado</span>' );
			return;
		}

		if ( 'mp4' === type ) {
			$preview.html( '<video src="' + url + '" muted loop playsinline width="100"></video>' );
		} else {
			$preview.html( '<img src="' + url + '" alt="" width="100">' );
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

		$( '.lsc-upload-button' ).on( 'click', function ( event ) {
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

		$( '.lsc-remove-button' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $button = $( this );
			var $row    = $button.closest( 'tr' );

			$row.find( '.lsc-url-field' ).val( '' );
			$row.find( '.lsc-type-field' ).val( '' );
			$row.find( '.lsc-attachment-field' ).val( '' );
			$button.prop( 'disabled', true );

			updatePreview( $row, '', '' );
		} );
	} );
})( jQuery );
