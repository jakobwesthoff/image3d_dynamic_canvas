Image3D = {
    container: false,
    canvas: false,
    controlOverlay: false,
    buttonList: false,
    buttons: new Array(),
    bodyElement: false,
    mouseEventGenerator: false,                

    init: function() 
    {
        Image3D.container = document.createElement( 'div' );
        Image3D.canvas = document.createElement( 'canvas' );
        Image3D.controlOverlay = document.createElement( 'div' );
        Image3D.container.style.position = "relative";
        Image3D.container.style.width = %width% + "px";
        Image3D.container.style.height = %height% + "px";
        Image3D.canvas.style.position = "absolute";
        Image3D.canvas.style.top = "0px";
        Image3D.canvas.style.left = "0px";
        Image3D.canvas.style.width = %width% + "px";
        Image3D.canvas.style.height = %height% + "px";
        Image3D.canvas.width = %width%;
        Image3D.canvas.height = %height%;
        Image3D.controlOverlay.style.position = "absolute";
        Image3D.controlOverlay.style.top = "0px";
        Image3D.controlOverlay.style.left = "0px";
        Image3D.controlOverlay.style.width = %width% + "px";
        Image3D.controlOverlay.style.height = %height% + "px";
        Image3D.buttons = new Array();
        Image3D.buttonList = document.createElement( 'ul' );

        // Create a new MouseEventGenerator
        var bodyElement = document.getElementsByTagName( "body" );
        Image3D.bodyElement = bodyElement[0];
        Image3D.mouseEventGenerator = new MouseEventGenerator( Image3D.controlOverlay, Image3D.bodyElement, Image3D.bodyElement );
        
        // Create the needed toolbar buttons
        var image = false;

        Image3D.buttons[0] = document.createElement( 'li' );
        image = document.createTextNode( 'T XY' );
        Image3D.buttons[0].addEventListener( 'click', function( event ) 
        {
            Image3D.mouseEventGenerator.setControlState( event, MouseEventGenerator.CONTROL_TRANSLATE_XY );
        }, false);
        Image3D.buttons[0].appendChild( image );

        Image3D.buttons[1] = document.createElement( 'li' );
        image = document.createTextNode( 'T Z' );
        Image3D.buttons[1].addEventListener( 'click', function( event ) 
        {
            Image3D.mouseEventGenerator.setControlState( event, MouseEventGenerator.CONTROL_TRANSLATE_Z );
        }, false);
        Image3D.buttons[1].appendChild( image );

        Image3D.buttons[2] = document.createElement( 'li' );
        image = document.createTextNode( 'R XY' );
        Image3D.buttons[2].addEventListener( 'click', function( event ) 
        {
            Image3D.mouseEventGenerator.setControlState( event, MouseEventGenerator.CONTROL_ROTATE_XY );
        }, false);
        Image3D.buttons[2].appendChild( image );

        Image3D.buttons[3] = document.createElement( 'li' );
        image = document.createTextNode( 'R Z' );
        Image3D.buttons[3].addEventListener( 'click', function( event ) 
        {
            Image3D.mouseEventGenerator.setControlState( event, MouseEventGenerator.CONTROL_ROTATE_Z );
        }, false);
        Image3D.buttons[3].appendChild( image );

        Image3D.buttons[4] = document.createElement( 'li' );
        image = document.createTextNode( 'PNG' );
        Image3D.buttons[4].addEventListener( 'click', function( event ) 
        {
            var oldDriver = Image3D.renderer.getDriver();
            Image3D.renderer.setDriver( new PngDriver() );
            Image3D.renderer.render();
            Image3D.renderer.setDriver( oldDriver );
            return false;
        }, false);
        Image3D.buttons[4].appendChild( image );

        Image3D.buttons[5] = document.createElement( 'li' );
        image = document.createTextNode( 'SVG' );
        Image3D.buttons[5].addEventListener( 'click', function( event ) 
        {
            var oldDriver = Image3D.renderer.getDriver();
            Image3D.renderer.setDriver( new SvgDriver() );
            Image3D.renderer.render();
            Image3D.renderer.setDriver( oldDriver );
            return false;
        }, false);
        Image3D.buttons[5].appendChild( image );

        // Add all buttons to the unordered list
        for( var key in Image3D.buttons ) 
        {
            Image3D.buttonList.appendChild( Image3D.buttons[key] );
        }
        
        // Put everything together and add it to the document
        Image3D.container.appendChild( Image3D.canvas );
        Image3D.controlOverlay.appendChild( Image3D.buttonList );
        Image3D.container.appendChild( Image3D.controlOverlay );
        scriptTag.parentNode.insertBefore( Image3D.container, scriptTag.nextSibling );

        // Init a new renderer
        Image3D.renderer = new Renderer();
        Image3D.renderer.setDriver( new CanvasDriver( Image3D.canvas ) );
        Image3D.renderer.setEventGenerator( Image3D.mouseEventGenerator );

        // Render the scene for the first time
        Image3D.renderer.render();
    }
}
