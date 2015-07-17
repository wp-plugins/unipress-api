var unipress_admin_jquery = jQuery.noConflict();

unipress_admin_jquery(document).ready(function($) {
	
	$( '#unipress_administrator_options' ).on( 'click', '#add-new-susbcription-match', function( event ) {
		event.preventDefault();
        var data = {
            'action': 'unipress-api-add-new-subscription-matching-row',
            'count': unipress_subscription_ids_iteration
        }
        unipress_subscription_ids_iteration++;
        $.post( ajaxurl, data, function( response ) {
            $( 'div#subscription-ids-matching' ).append( response );
        });
	});
	
	$( '#unipress_administrator_options' ).on( 'click', '.subscription-id-delete', function( event ) {
        event.preventDefault();
        $( this ).closest( '.subscription-id-match' ).remove();
	});
	
	$( '#unipress_administrator_options' ).on( 'change', '#unipress-excerpt-type', function( event ) {
		if ( 'content' === $( 'option:selected', this ).val() ) {
			$( 'span#unipress-excerpt-size' ).show();
		} else {
			$( 'span#unipress-excerpt-size' ).hide();
		}
	});
	
});