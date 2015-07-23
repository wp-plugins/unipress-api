var unipress_push_jquery = jQuery.noConflict();

unipress_push_jquery(document).ready(function($) {
	
	$( '#unipress-push-metabox' ).on( 'change', 'select#unipress-push-type', function( event ) {
		max_length = ( 'android' === $( 'option:selected', this ).val() ) ? UNIPRESS_API_ANDROID_MAX_CHAR : UNIPRESS_API_IOS_MAX_CHAR;
		$( 'span#push-max-length' ).text( max_length );
	});

	$( '#unipress-push-metabox' ).on( 'keyup paste', 'textarea#unipress-push-content', function( event ) {
		var value = $( this ).val().replace( /\r\n/g, '\n' ),
			byte_content = utf8.encode( value ), // https://mths.be/utf8js
			byte_count = utf8.encode( value ).length; // https://mths.be/utf8js

		max_length = $( 'span#push-max-length' ).text();
		remaining = max_length - byte_count;
		if ( 0 > remaining ) {
			var sub = byte_content.substring( 0, max_length - 1 );
			var decoded = utf8.decode( sub );
			$( this ).val( decoded );
			byte_count = utf8.encode( decoded ).length;
		} else if ( 15 > remaining ) {
			$( 'span#push-current-length' ).removeClass();
			$( 'span#push-current-length' ).addClass( 'unipress-push-count-superwarn' );
		} else if ( 30 > remaining ) {
			$( 'span#push-current-length' ).removeClass();
			$( 'span#push-current-length' ).addClass( 'unipress-push-count-warn' );
		} else {
			$( 'span#push-current-length' ).removeClass();
			$( 'span#push-current-length' ).addClass( 'unipress-push-count' );
		}
		$( 'span#push-current-length' ).text( byte_count );
	});

});