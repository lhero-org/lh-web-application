/**
 * Media library pickers for the LH Web Application settings screen and the
 * shortcut menu icons on Appearance > Menus.
 *
 * Each picker is a button whose id ends in -upload_button. Its prefix (the id
 * without that ending) names the fields it fills: {prefix}-attachment_url shows
 * the image URL, and the hidden attachment ID field is {prefix}_attachment_id
 * on the settings screen or {prefix}-attachment_id on the menus screen.
 * Emptying the URL field clears the attachment ID too.
 */
( function( $ ) {

	function idField( prefix ) {

		var field = $( document.getElementById( prefix + '_attachment_id' ) );

		if ( ! field.length ) {
			field = $( document.getElementById( prefix + '-attachment_id' ) );
		}

		return field;

	}

	$( document ).on( 'click', 'input[id$="-upload_button"]', function( event ) {

		if ( typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		event.preventDefault();

		var prefix = this.id.replace( /-upload_button$/, '' );
		var id_field = idField( prefix );
		var url_field = $( document.getElementById( prefix + '-attachment_url' ) );

		var frame = wp.media( {
			title: this.value,
			button: { text: this.value },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function() {

			var attachment = frame.state().get( 'selection' ).first().toJSON();

			id_field.val( attachment.id ).trigger( 'change' );
			url_field.val( attachment.url ).trigger( 'change' );

		} );

		frame.open();

	} );

	$( document ).on( 'input change', 'input[id$="-attachment_url"]', function() {

		if ( '' === this.value.trim() ) {
			idField( this.id.replace( /-attachment_url$/, '' ) ).val( '' );
		}

	} );

} )( jQuery );
