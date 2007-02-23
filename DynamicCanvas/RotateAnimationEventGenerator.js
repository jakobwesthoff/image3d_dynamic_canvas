/**
 * Class to generate timer based events to roate the object
 * ( For testing purpose only )
 */
function RotateAnimationEventGenerator() {
    this._timeout( this );
}

RotateAnimationEventGenerator.prototype = {
    /**
     * Renderer to send events to 
     */
    _renderer: false,

    /**
     * Attach to a renderer 
     */
    attach: function( renderer ) {
        // Set the renderer to notify of events
        this._renderer = renderer;
    },

    /**
     * Detach from a renderer 
     */
    detach: function() {
        this._renderer = false;
    },

    /**
     * Notify the renderer of an occured event 
     */
    _notifyRenderer: function( event, data ) {
        if ( this._renderer != false ) 
        {
            this._renderer.notify( event, data );
        }
    },

    /**
     * Called every 10 ms to send rotation event 
     */
    _timeout: function( self ) {
        self._notifyRenderer( Renderer.EVENT_ROTATE, [ 2, 4, 2 ] );
        window.setTimeout( function(){ self._timeout( self ); }, 10 );                    
    }
}
