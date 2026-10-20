( function( wp, data ) {

	const { registerPlugin } = wp.plugins;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { select, dispatch } = wp.data;

	// Moved from wp.editPost to wp.editor in WordPress 6.6
	const MoreMenuItem = ( wp.editor && wp.editor.PluginMoreMenuItem ) || ( wp.editPost && wp.editPost.PluginMoreMenuItem );

	if ( ! MoreMenuItem || ! data ) return;

	const startExport = ( url ) => {

		const editor = select( 'core/editor' );
		const notices = dispatch( 'core/notices' );

		if ( editor.isEditedPostNew() ) {

			notices.createNotice( 'warning', __( 'Save this content before exporting it.', 'single-post-page-export' ), { id: 'ss88-sppe-export', isDismissible: true } );
			return;

		}

		if ( editor.isEditedPostDirty() ) {

			notices.createNotice( 'info', __( 'You have unsaved changes. The export contains the last saved version.', 'single-post-page-export' ), { id: 'ss88-sppe-export', type: 'snackbar' } );

		}

		// A hidden frame avoids the editor's "leave site?" prompt. Unlike <a download> it's a real
		// navigation, so it carries the login cookie and works with service workers (e.g. Playground)
		const frame = document.createElement( 'iframe' );
		frame.style.display = 'none';
		frame.src = url;

		// Downloads don't render a page, so a load with content means WordPress returned an error
		frame.addEventListener( 'load', () => {

			let message = '';

			try {

				const doc = frame.contentDocument;
				const error = doc && ( doc.querySelector( '.wp-die-message' ) || doc.body );
				message = error ? error.textContent.trim() : '';

			} catch ( e ) {}

			if ( message ) {

				notices.createNotice( 'error', message, { id: 'ss88-sppe-export', isDismissible: true } );

			}

			frame.remove();

		} );

		document.body.appendChild( frame );

		// Some browsers never fire load for downloads
		setTimeout( () => frame.remove(), 60000 );

	};

	registerPlugin( 'ss88-sppe-export', {
		render: () => el(
			Fragment,
			null,
			el( MoreMenuItem, { icon: 'download', onClick: () => startExport( data.exportUrl ) }, data.exportLabel ),
			el( MoreMenuItem, { icon: 'download', onClick: () => startExport( data.exportMediaUrl ) }, __( 'Export with media', 'single-post-page-export' ) )
		),
	} );

} )( window.wp, window.SS88_SPPE_EditorData );
