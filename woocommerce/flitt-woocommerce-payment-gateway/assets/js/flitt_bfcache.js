/**
 * When the checkout page is restored from the browser's back-forward cache (bfcache)
 * — e.g. the customer pressed Back from the Flitt payment page — the "Place order"
 * button can stay in its disabled/processing state, because the whole page (including
 * the WooCommerce Blocks React state) is restored frozen. Reloading gives a fresh,
 * clickable checkout again.
 */
( function () {
    window.addEventListener( 'pageshow', function ( event ) {
        if ( event.persisted ) {
            window.location.reload();
        }
    } );
} )();
