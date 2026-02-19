/**
 * Custom Guest Authors — Classic Editor Meta Box
 *
 * Handles client-side behaviour for the classic editor meta box.
 * Currently lightweight — primarily guards against accidental form submission
 * on Enter and trims whitespace from the input before save.
 */
( function ( $ ) {
    'use strict';

    $( function () {
        var $input = $( '#cga_guest_author' );

        if ( ! $input.length ) {
            return;
        }

        // Prevent Enter key from submitting the post form unintentionally
        $input.on( 'keydown', function ( e ) {
            if ( e.key === 'Enter' ) {
                e.preventDefault();
            }
        } );

        // Trim whitespace from the value before the post form is submitted
        $( '#post' ).on( 'submit', function () {
            $input.val( $.trim( $input.val() ) );
        } );
    } );
} )( jQuery );
