<?php
/**
 * 3d Library
 *
 * PHP versions 5
 *
 * LICENSE:
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   Image
 * @package    Image_3D
 * @author     Jakob Westhoff <jakob@westhoffswelt.de>
 */

/**
 * Creates a HTML document, with embedded javascript code to draw, move, rotate
 * and export the 3D-object at runtime
 *
 * @category   Image
 * @package    Image_3D
 * @author     Jakob Westhoff <jakob@westhoffswelt.de>
 */
class Image_3D_Driver_DynamicCanvas extends Image_3D_Driver {

    /**
     * Width of the image
     *
     * @var integer
     */
    protected $_x;
    /**
     * Height of the image
     *
     * @var integer
     */
    protected $_y;

    /**
     * Current, increasing unique identifier.
     * Needed to create gradient identfiers
     *
     * @var integer
     */
    protected $_id;

    /**
     * Polygones created during the rendering process
     *
     * @var array
     */
    protected $_polygones;

    /**
     * Background Color of the rendered image
     *
     * @var string
     */
    protected $_background;


    /**
     * Name of the Render created from the filename
     * Needed for the correct creation of the Image3D java class
     * 
     * @var mixed
     */
    protected $_name;

    /**
     * Class constructor
     */
    public function __construct() 
    {
        $this->_image = '';
        $this->_id = 1;
        $this->_polygones = array();
        $this->_background = array();
    }

    /**
     * Create the inital image
     * 
     * @param float $x Width of the image
     * @param float $y Height of the image
     * @return void
     */
    public function createImage( $x, $y ) 
    {
        $this->_x = ( int ) $x;
        $this->_y = ( int ) $y;
    }

    /**
     * Set the background color of the image 
     * 
     * @param Image_3D_Color $color Desired background color of the image
     * @return void
     */
    public function setBackground( Image_3D_Color $color ) 
    {
        $colorarray = $this->_getRgba( $color );
        $this->_background = sprintf( "{ r: %d, g: %d, b: %d, a:%.2f }",$colorarray['r'], $colorarray['g'], $colorarray['b'], $colorarray['a'] );
    }

    /**
     * Create an appropriate array representation from a Image_3D_Color object
     * 
     * @param Image_3D_Color $color Color to transform to rgba syntax
     * @param float $alpha optional Override the alpha value set in the Image_3D_Color object
     * @return array Array of color values reflecting the different color
     *               components of the input object
     */ 
    protected function _getRgba( Image_3D_Color $color, $alpha = null ) 
    {
        $values = $color->getValues();

        $values[0] = ( int ) round( $values[0] * 255 );
        $values[1] = ( int ) round( $values[1] * 255 );
        $values[2] = ( int ) round( $values[2] * 255 );

        if ( $alpha !== null ) 
        {
            $values[3] = 1.0 - $alpha;
        } 
        else 
        {
            $values[3] = 1.0 - $values[3];
        }

        return array( 'r' => $values[0], 'g' => $values[1], 'b' => $values[2], 'a' => $values[3] );
    }

    /**
     * Add a polygon to the polygones array 
     * 
     * @param array $points Array of points which represent the polygon to add
     * @param array $colors Array of maximal three colors. The second and the
     *                      third color are allowed to be null
     * @return void
     */
    protected function _addPolygon( array $points, array $colors ) 
    {        
        $this->_polygones[] = array( "points" => $points, "colors" => $colors );
    }

    /**
     * Draw a specified polygon 
     * 
     * @param Image_3D_Polygon $polygon Polygon to draw
     * @return void
     */
    public function drawPolygon( Image_3D_Polygon $polygon ) 
    {
        $pointarray = array();
        $points = $polygon->getPoints();
        foreach ( $points as $key => $point ) 
        {
            $pointarray[$key] = array( 'x' => $point->getX(), 'y' => $point->getY(), 'z' => $point->getZ() );
        }

        $this->_addPolygon( 
            $pointarray,
            array( 
                $this->_getRgba( $polygon->getColor() ),
                null,
                null
            )
       );
    }

    /**
     * Draw a specified polygon utilizing gradients between his points for
     * color representation (Gauroud-Shading)
     * 
     * @param Image_3D_Polygon $polygon Polygon to draw
     * @return void
     */
    public function drawGradientPolygon( Image_3D_Polygon $polygon ) 
    {
        $pointarray = array();
        $colorarray = array();
        $points = $polygon->getPoints();
        foreach ( $points as $key => $point ) 
        {
            $pointarray[$key] = array( 'x' => $point->getX(), 'y' => $point->getY(), 'z' => $point->getZ() );
            $colorarray[$key] = $this->_getRgba( $point->getColor() );
        }

        $this->_addPolygon( 
            $pointarray,
            $colorarray
       );
    }

    /**
     * Convert php array to a javascript parsable data structure
     * 
     * @param array $data Array to convert
     * @return string Javascript readable representation of the given php array
     */
    private function _arrayToJs( array $data ) 
    {
        $output = array();
        $assoiative = false;
        // Is our array associative?
        // Does anyone know a better/faster way to check this?
        foreach( array_keys( $data ) as $key ) 
        {
            if ( is_int( $key ) === false ) 
            {
                $assoiative = true;
                break;
            }
        }
        $output[] = $assoiative === true ? "{" : "[";
        foreach( $data as $key => $value ) 
        {
            $line = '';
            if ( $assoiative === true )
            {
                $line .= "\"$key\": "; 
            }
            switch ( gettype( $value ) ) 
            {
                case "array":
                    $line .= $this->_arrayToJs( $value );
                break;
                case "integer":
                case "boolean":
                    $line .= $value;
                break;
                case "double":
                    $line .= sprintf( "%.2f", $value );
                break;
                case "string":
                    $line .= "\"$value\"";
                break;
                case "NULL":
                case "resource":
                case "object":
                    $line .= "undefined";
                break;
            }
            if( $key !== end( array_keys( $data ) ) )
            {
                $line .= ",";
            }
            $output[] = $line;
        }
        
        $output[] = $assoiative === true ? "}" : "]";

        // If the output array has more than 5 entries seperate them by a new line.
        return implode( count( $data ) > 5 ? "\n" : " ", $output );
    }

    /**
     * Get the Javascript needed for dynamic rendering, moving, rotating
     * and exporting of the 3D Object
     * 
     * @return string needed javascript code (with <script> tags)
     */
    private function _getJs() 
    {
        $polygoneArray = $this->_arrayToJs( $this->_polygones ) . ";\n";

        return <<<EOF
            // We need to know where the script tag of this file is in the dom
            var allScriptTags = document.documentElement.getElementsByTagName( 'script' );
            var scriptTag = false;
            if ( allScriptTags.length > 1 )
            {
                for ( var key in allScriptTags )
                {
                    scriptTag = allScriptTags[key];
                }
            }
            else 
            {
                scriptTag = allScriptTags[0];
            }
            // scriptTag is now the last added script, so it should be ours.

            // Data section *start*
            var polygones = {$polygoneArray}
            // Data section *end*


            /**
             * Class to render the given set of polygones
             * Rendering will be done using a specified output driver
             * All further manipulations are based on an event generator
             * which is associated with the renderer
             */
            function Renderer() 
            {
                // Generate sinus and cosinus lookup tables
                this._generateSinCosTables();
            }

            // Class constants
            Renderer.EVENT_TRANSLATE = 1;
            Renderer.EVENT_ROTATE = 2;

            Renderer.prototype = {
                /**
                 * Width of the image
                 */
                _imageSizeX: {$this->_x}.0,
                /**
                 * Height of the image 
                 */
                _imageSizeY: {$this->_y}.0,

                /**
                 * Cos values (0-360 degrees, step 1 ) 
                 */
                _cos: Array(),
                /**
                 * Sin values ( 0-360 degrees, step 1 )
                 */
                _sin: Array(),

                /**
                 * Viewpoint used for rendering 
                 */
                _viewpoint: 500.0,
                /**
                 * Distance used for rendering 
                 */
                _distance: 500.0,
                
                /**
                 * Output driver which renders the actual data 
                 */
                _driver: false,

                /**
                 * Event generator to notify the renderer 
                 */
                _eventGenerator: false,
                
                /**
                 * Generate sinus and cosinus lookup tables for faster access
                 */
                _generateSinCosTables: function() {
                    var factor = Math.PI*2 / 360;
                    this._sin = new Array();
                    this._cos = new Array();
                    for(var i=0; i<=360; i++) {
                        this._sin[i] = Math.sin(factor*i);
                        this._cos[i] = Math.cos(factor*i);
                    }
                },

                /**
                 * Set a driver to use for output rendering
                 */
                setDriver: function( driver ) {
                    // Delete the old driver class from memory
                    delete this._driver;

                    // Set the new one
                    this._driver = driver;
                },

                /**
                 * Set an event generator to listen to 
                 */
                setEventGenerator: function( eventGenerator ) {
                    // Delete the old event generator to free memory and stop it from notifying the renderer
                    if ( this._eventGenerator != false ) 
                    {
                        this._eventGenerator.detach();
                    }
                    delete this._eventGenerator;

                    // Set new event generator and attach it to this renderer
                    this._eventGenerator = eventGenerator;
                    this._eventGenerator.attach( this );
                },

                /**
                 * Set the viewpoint for rendering 
                 */
                setViewpoint: function( viewpoint ) {
                    this._viewpoint = viewpoint;
                },
                
                /**
                 * Set the distance for rendering 
                 */
                setDistance: function( distance ) {
                    this._distance = distance;
                },

                /**
                 * Compare two polygones by their medium z distance
                 * Used for sorting the polygones array
                 */
                _sortByMidZ: function( polygon1, polygon2 ) 
                {
                    var midZ_polygon1 = 0.0;
                    var midZ_polygon2 = 0.0;
                    
                    for( var i = 0; i<polygon1["points"].length; i++ ) 
                    {
                        midZ_polygon1 += polygon1["points"][i]["z"];
                    }
                    midZ_polygon1 = midZ_polygon1 / parseFloat( polygon1["points"].length );

                    for( var i = 0; i<polygon2["points"].length; i++ ) 
                    {
                        midZ_polygon2 += polygon2["points"][i]["z"];
                    }
                    midZ_polygon2 = midZ_polygon2 / parseFloat( polygon2["points"].length );

                    return midZ_polygon2 - midZ_polygon1;
                },

                /**
                 * Sort the polygones by their medium z distance
                 */
                _sortPolygones: function() 
                {
                    polygones.sort( this._sortByMidZ );
                },

                /**
                 * Apply a specified rotation on all of the polygones
                 */
                _rotate: function( rx, ry, rz ) 
                {
                    for( var i = 0; i<polygones.length; i++ ) 
                    {
                        for ( var j = 0; j<polygones[i]["points"].length; j++ )
                        {
                            var px = polygones[i]["points"][j]["x"];
                            var py = polygones[i]["points"][j]["y"];
                            var pz = polygones[i]["points"][j]["z"];

                            // Rotate around the z axis
                            if ( rz != 0 ) 
                            {
                                var x = this._cos[rz] * px + this._sin[rz] * py;
                                var y = -this._sin[rz] * px + this._cos[rz] * py;
                                var z = pz;

                                px = x; py = y; pz = z;                           
                            }

                            // Rotate around the x axis
                            if ( rx != 0 ) 
                            {
                                var x = px;
                                var y = this._cos[rx] * py + ( -this._sin[rx] * pz );
                                var z = this._sin[rx] * py + this._cos[rx] * pz;

                                px = x; py = y; pz = z;                           
                            }

                            // Rotate around the y axis
                            if ( ry != 0 ) 
                            {
                                var x = this._cos[ry] * px + this._sin[ry] * pz;
                                var y = py;
                                var z = -this._sin[ry] * px + this._cos[ry] * pz;

                                px = x; py = y; pz = z;                           
                            }

                            polygones[i]["points"][j]["x"] = px;
                            polygones[i]["points"][j]["y"] = py;
                            polygones[i]["points"][j]["z"] = pz;
                        }
                    }
                },

                /**
                 * Apply a specified translation on all of the polygones
                 */
                _translate: function( tx, ty, tz ) 
                {
                    for( var i = 0; i<polygones.length; i++ ) 
                    {
                        for ( var j = 0; j<polygones[i]["points"].length; j++ )
                        {
                            polygones[i]["points"][j]["x"] = polygones[i]["points"][j]["x"] + tx;
                            polygones[i]["points"][j]["y"] = polygones[i]["points"][j]["y"] + ty;
                            polygones[i]["points"][j]["z"] = polygones[i]["points"][j]["z"] + tz;
                        }
                    }
                },


                /**
                 *  A new event has been occured
                 */
                notify: function( event, data ) {
                    switch ( event ) 
                    {
                        case Renderer.EVENT_TRANSLATE:
                            this._translate( data[0], data[1], data[2] );
                        break;
                        case Renderer.EVENT_ROTATE:
                            this._rotate( data[0], data[1], data[2] );
                        break;
                    }
                    this.render();
                },

                /**
                 * Calculate screen coordinates for every polygon and render it 
                 */
                render: function() {
                    // Begin output
                    this._driver.begin( this._imageSizeX, this._imageSizeY );

                    // Sort all polygones by their Z-Order
                    this._sortPolygones();
                    
                    // Draw the background
                    this._driver.drawPolygon( { points: [ [ 0, 0 ], [ this._imageSizeX, 0 ], [ this._imageSizeX, this._imageSizeY ], [ 0, this._imageSizeY ] ], colors: [ {$this->_background}, undefined, undefined ] } );

                    // Calculate screen coordinate for every polygon point and send it to the driver for drawing
                    for( var i = 0; i<polygones.length; i++ ) 
                    {
                        var screenCoords = new Array();
                        for ( var j = 0; j<polygones[i]["points"].length; j++ ) 
                        {
                            screenCoords.push( 
                                [ 
                                    this._viewpoint * polygones[i]["points"][j]["x"] / (polygones[i]["points"][j]["z"] + this._distance) + this._imageSizeX/2,
                                    this._viewpoint * polygones[i]["points"][j]["y"] / (polygones[i]["points"][j]["z"] + this._distance) + this._imageSizeY/2
                                ]
                            );
                        }
                        this._driver.drawPolygon( { points: screenCoords, colors: polygones[i]["colors"] } );
                    }

                    // Tell the driver to finish his work
                    this._driver.finish();
                }
            }


            /**
             * Output Driver to render into a canvas object
             */
            function CanvasDriver( canvasElement ) {
                if ( !canvasElement.getContext ) 
                {            
                    window.alert( 'Unfortunatly your browser does not support the "Canvas" control.\\nDownload Firefox <http://mozilla.org/firefox> to make the 3D control display in your browser.' );
                    throw "Canvas Control not available.";
                }

                this._canvas = canvasElement.getContext( '2d' );
            }

            CanvasDriver.prototype = {
                /**
                 * Canvas rendering context 
                 */
                _canvas: false,

                /**
                 * Create a "rgba" string from a specified color array
                 */
                _getRgba: function( color ) 
                {
                    if ( arguments[1] != undefined ) 
                    {
                        color["a"] = arguments[1];
                    }
                    return "rgba( " + color["r"] + ", " + color["g"] + ", " + color["b"] + ", " + color["a"] + " )";
                },

                /**
                 * Starts output by recieving width and height of the image
                 * to be rendered 
                 */
                begin: function( x, y ) {
                    // Nothing to do here
                },

                /**
                 * Draw a given polygon into the approriate context
                 */
                drawPolygon: function( polygon ) {
                    this._canvas.beginPath();
                    this._canvas.moveTo( polygon["points"][0][0], polygon["points"][0][1] );
                    for ( var j = 1; j<polygon["points"].length; j++ ) 
                    {
                        this._canvas.lineTo( polygon["points"][j][0], polygon["points"][j][1] );                            
                    }
                    this._canvas.lineTo( polygon["points"][0][0], polygon["points"][0][1] );
                    if ( polygon["colors"][1] == undefined ) 
                    {
                        // Only one color, means flat shading
                        this._canvas.fillStyle = this._getRgba( polygon["colors"][0] );
                        this._canvas.fill();                
                    }
                    else 
                    {
                        // More than one color. Gauroud shading is used

                        // Create the main gradient between the first and the second point
                        var mainGradient = this._canvas.createLinearGradient( polygon["points"][0][0], polygon["points"][0][1], polygon["points"][1][0], polygon["points"][1][1] );
                        mainGradient.addColorStop( 0.0, this._getRgba( polygon["colors"][0] ) );
                        mainGradient.addColorStop( 1.0, this._getRgba( polygon["colors"][1] ) );
                        
                        // Create the overlay gradient between the third point and the inbetween of the two other points
                        var overlayGradient = this._canvas.createLinearGradient( 
                            polygon["points"][2][0],
                            polygon["points"][2][1],
                            ( polygon["points"][0][0] + polygon["points"][1][0] ) / 2.0,
                            ( polygon["points"][0][1] + polygon["points"][1][1] ) / 2.0
                        );
                        overlayGradient.addColorStop( 0.0, this._getRgba( polygon["colors"][2] ) );
                        overlayGradient.addColorStop( 1.0, this._getRgba( polygon["colors"][2], 0.0 ) );
                        
                        // Draw the gradients
                        this._canvas.fillStyle = mainGradient;
                        this._canvas.fill();
                        this._canvas.fillStyle = overlayGradient;
                        this._canvas.fill();

                        // Delete the gradients to free memory
                        delete mainGradient;
                        delete overlayGradient;
                    }
                },

                /**
                 * Finish the output 
                 */
                finish: function() {
                    // Nothing to do here
                }
            }

            /**
             * Driver which outputs a png image of the rendering context
             */
            function PngDriver() {
                // Nothing to do here
            }

            PngDriver.prototype = {
                /**
                 * The in-memory canvas element, where the image will be
                 * rendered to before it is saved to a png
                 */
                _canvasElement: false,

                /**
                 * Begin output by creating in-memory canvas to draw to 
                 */
                begin: function( x, y ) {
                    this._canvasElement = document.createElement( 'canvas' );
                    this._canvasElement.width = x;
                    this._canvasElement.height = y;

                    if ( !this._canvasElement.toDataURL ) 
                    {
                        window.alert('Sorry your browser does not support export to an image file.');
                        throw "Canvas does not support toDataURL";
                    }
                    this._canvasDriver = new CanvasDriver( this._canvasElement );                
                },

                /**
                 * Draw a given polygon 
                 */
                drawPolygon: function( polygon ) 
                {
                    this._canvasDriver.drawPolygon( polygon );
                },
                
                /**
                 *  Finish the output process
                 */
                finish: function() {
                    window.location = this._canvasElement.toDataURL();
                }
            }

            /**
             * EventGenerator which enables controlling the render by mouse movements
             */
            function MouseEventGenerator( activateObject, deactivateObject, movementObject ) {
                this._activateObject = activateObject;
                this._deactivateObject = deactivateObject;
                this._movementObject = movementObject;
                this.setControlState( null, MouseEventGenerator.CONTROL_TRANSLATE_XY );
            }           

            // Class constants
            MouseEventGenerator.CONTROL_TRANSLATE_XY = 1;
            MouseEventGenerator.CONTROL_TRANSLATE_Z = 2;
            MouseEventGenerator.CONTROL_ROTATE_XY = 3;
            MouseEventGenerator.CONTROL_ROTATE_Z = 4;

            MouseEventGenerator.prototype =  {
                /**
                 * The renderer which should be notified 
                 */
                _renderer: false,

                /**
                 * Dom object to register mousedown event on
                 */
                _activateObject: false,
                /**
                 * Dom object to register mouseup event on
                 */
                _deactivateObject: false,
                /**
                 * Dom object to register mousemove event on
                 */
                _movementObject: false,

                /**
                 * Anonymous function to be called on activation
                 * Needed to compensate javascripts strange "this" behaviour
                 */
                _activateFunction: false,
                /**
                 * Anonymous function to be called on deactivation
                 * Needed to compensate javascripts strange "this" behaviour
                 */
                _deactivateFunction: false,
                /**
                 * Anonymous function to be called on movement
                 * Needed to compensate javascripts strange "this" behaviour
                 */
                _movementFunction: false,
                
                /**
                 * Last captured x mouse position 
                 */
                _lastMouseX: 0,
                /**
                 * Last captured y mouse position 
                 */
                _lastMouseY: 0,
                /**
                 * Calculated x offset from the last mouse position
                 */
                _mouseXOffset: 0,
                /**
                 * Calculated y offset from the last mouse position 
                 */
                _mouseYOffset: 0,

                /**
                 * Manipulation state 
                 */
                _inProgress: false,

                /**
                 * Current control state 
                 */
                _currentControlState: 0,

                /**
                 * Attach to a renderer 
                 */
                attach: function( renderer ) {
                    // Register events on the appropriate DOM objects
                    var self = this;
                    this._activateFunction = function( event ) { self._onMouseDown( event ); };
                    this._deactivateFunction = function( event ) { self._onMouseUp( event ); };
                    this._movementFunction = function( event ) { self._onMouseMove( event ); };
                    this._activateObject.addEventListener( 'mousedown', this._activateFunction , true );
                    this._deactivateObject.addEventListener( 'mouseup', this._deactivateFunction, true );
                    this._movementObject.addEventListener( 'mousemove', this._movementFunction, true );
                    // Set the renderer to notify of events
                    this._renderer = renderer;
                },

                /**
                 * Detach from a renderer 
                 */
                detach: function() {
                    // Stop notifying the renderer of events
                    this._renderer = false;

                    // Remove the registered event listeners
                    this._activateObject.removeEventListener( 'mousedown', this._activateFunction, false );
                    this._deactivateObject.removeEventListener( 'mouseup', this._deactivateFunction, false );
                    this._movementObject.removeEventListener( 'mousemove', this._movementFunction, false );
                },

                /**
                 * Notify the attached renderer of an occured event 
                 */
                _notifyRenderer: function( event, data ) {
                    if ( this._renderer != false ) 
                    {
                        this._renderer.notify( event, data );
                    }
                },

                /**
                 * Capture any mousemove event
                 */
                _onMouseMove: function( event ) 
                {
                    var progressOffset = 4;
                    var calcOffsetX = 0;
                    var calcOffsetY = 0;

                    if( !this._inProgress ) 
                    {   
                        return;
                    }

                    if ( this._mouseXOffset < progressOffset && this._mouseXOffset > -progressOffset ) 
                    {
                        this._mouseXOffset += this._lastMouseX - event.clientX;
                        calcOffsetX = 0;
                    }
                    else
                    {
                        calcOffsetX = Math.round( ( this._lastMouseX - event.clientX ) / progressOffset )
                        this._lastMouseX = event.clientX;
                        this._mouseXOffset = 0;
                    }

                    if ( this._mouseYOffset < progressOffset && this._mouseYOffset > -progressOffset ) 
                    {
                        this._mouseYOffset += this._lastMouseY - event.clientY;
                        calcOffsetY = 0;
                    }
                    else
                    {
                        calcOffsetY = Math.round( ( this._lastMouseY - event.clientY ) / progressOffset )
                        this._lastMouseY = event.clientY;
                        this._mouseYOffset = 0;
                    }


                    if ( calcOffsetX != 0 || calcOffsetY != 0 ) 
                    {
                        switch ( this._currentControlState ) 
                        {
                            case MouseEventGenerator.CONTROL_TRANSLATE_XY:
                                this._notifyRenderer( Renderer.EVENT_TRANSLATE, [ -calcOffsetX * 2, -calcOffsetY * 2, 0 ] );
                            break
                            case MouseEventGenerator.CONTROL_TRANSLATE_Z:
                                this._notifyRenderer( Renderer.EVENT_TRANSLATE, [ 0, 0, -calcOffsetY * 2 ] );
                            break;
                            case MouseEventGenerator.CONTROL_ROTATE_XY:
                                this._notifyRenderer( Renderer.EVENT_ROTATE, [ calcOffsetY < 0 ? -calcOffsetY * 2 : 360 - calcOffsetY * 2, calcOffsetX < 0 ? 360 - -calcOffsetX * 2 : calcOffsetX * 2, 0 ] );
                            break;
                            case MouseEventGenerator.CONTROL_ROTATE_Z:
                                this._notifyRenderer( Renderer.EVENT_ROTATE, [ 0, 0, calcOffsetX < 0 ? 360 - -calcOffsetX * 2 : calcOffsetX * 2 ] );
                            break;
                        }
                    }
                },
                
                /**
                 * Capture any mousedown event
                 */
                _onMouseDown: function( event ) 
                {
                    this._lastMouseX = event.clientX;
                    this._lastMouseY = event.clientY;
                    this._inProgress = true;
                },

                /**
                 * Capture any mouseup event 
                 */
                _onMouseUp: function( event ) 
                {
                    this._inProgress = false;
                },

                /**
                 * Set the specified control state and change the control
                 * representation appropriatly
                 */ 
                setControlState: function( event, state ) 
                {
                    var buttons = new Array();
                    console.log( state );
/*                    buttons[1] = document.getElementById( "CONTROL_TRANSLATE_XY_BUTTON" );
                    buttons[2] = document.getElementById( "CONTROL_TRANSLATE_Z_BUTTON" );
                    buttons[4] = document.getElementById( "CONTROL_ROTATE_XY_BUTTON" );
                    buttons[8] = document.getElementById( "CONTROL_ROTATE_Z_BUTTON" );

                    buttons[MouseEventGenerator.CONTROL_TRANSLATE_XY].style.color = "#204a87";
                    buttons[MouseEventGenerator.CONTROL_TRANSLATE_Z].style.color = "#204a87";
                    buttons[MouseEventGenerator.CONTROL_ROTATE_XY].style.color = "#204a87";
                    buttons[MouseEventGenerator.CONTROL_ROTATE_Z].style.color = "#204a87";

                    buttons[state].style.color = "#f57900";
*/


                    this._currentControlState = state;
                }

            }

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
                    Image3D.container.style.width = {$this->_x} + "px";
                    Image3D.container.style.height = {$this->_y} + "px";
                    Image3D.canvas.style.position = "absolute";
                    Image3D.canvas.style.top = "0px";
                    Image3D.canvas.style.left = "0px";
                    Image3D.canvas.style.width = {$this->_x} + "px";
                    Image3D.canvas.style.height = {$this->_y} + "px";
                    Image3D.canvas.width = {$this->_x};
                    Image3D.canvas.height = {$this->_y};
                    Image3D.controlOverlay.style.position = "absolute";
                    Image3D.controlOverlay.style.top = "0px";
                    Image3D.controlOverlay.style.left = "0px";
                    Image3D.controlOverlay.style.width = {$this->_x} + "px";
                    Image3D.controlOverlay.style.height = {$this->_y} + "px";
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
                        Image3D.renderer.setDriver( new PngDriver() );
                        Image3D.renderer.render();
                        return false;
                    }, false);
                    Image3D.buttons[4].appendChild( image );

                    Image3D.buttons[5] = document.createElement( 'li' );
                    image = document.createTextNode( 'SVG' );
                    Image3D.buttons[5].addEventListener( 'click', function( event ) 
                    {
                        Image3D.renderer.setDriver( new SvgDriver() );
                        Image3D.renderer.render();
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

            function SvgDriver() {
            }

            SvgDriver.prototype = {
                _svg: "",
                _index: 0,
                _polygones: Array(),
                _gradients: Array(),

                _decimal2hex: function( decimal ) {
                    var charset = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"];
                    var hex = Array();

                    for( var d = decimal; d != 0; d = parseInt( d / 16 ) )
                    {
                        hex.unshift( charset[ d%16 ] ) 
                        
                    }
                    if ( hex.length == 0 ) 
                    {
                        hex.unshift( "0" );
                    }

                    return hex.join( "" );
                },

                _getStyle: function( color ) {
                    var rx = ( this._decimal2hex( color["r"] ).length < 2 ) ? "0" + this._decimal2hex( color["r"] ) : this._decimal2hex( color["r"] );
                    var gx = ( this._decimal2hex( color["g"] ).length < 2 ) ? "0" + this._decimal2hex( color["g"] ) : this._decimal2hex( color["g"] );
                    var bx = ( this._decimal2hex( color["b"] ).length < 2 ) ? "0" + this._decimal2hex( color["b"] ) : this._decimal2hex( color["b"] );

                    return "fill: #" + rx + gx + bx + "; fill-opacity: " + color["a"] + "; stroke: none;";
                },

                _getGradientStop: function( color, offset, alpha ) {
                },

                begin: function( x, y ) {
                    this._svg += '<?xml version="1.0" ?>\\n';
                    this._svg += '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"\\n';             
                    this._svg += '"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">\\n\\n';
                    this._svg += '<svg xmlns="http://www.w3.org/2000/svg" x="0" y="0" width="' + x + '" height="' + y + '">\\n';
                },

                drawPolygon: function( polygon ) {
                    var pointlist = ""
                    for( var i=0; i<polygon["points"].length; i++ ) 
                    {
                        pointlist += ( Math.round( polygon["points"][i][0] * 100 ) / 100 ) + "," + ( Math.round( polygon["points"][i][1] * 100 ) / 100 ) + " "; 
                    }
                    this._polygones.push( "<polygon points=\"" + pointlist.substr( 0, pointlist.length -1 ) + "\" style=\"" + this._getStyle( polygon["colors"][0] )  +"\" />\\n" );
                },

                finish: function() {
                    this._svg += "<defs></defs>\\n";
                    this._svg += this._polygones.join( "" );
                    this._svg += "</svg>";
                    window.location = "data:image/svg+xml;base64," + Base64.encode( this._svg );
                }
            }

            Base64 = {
                charset: [ "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "+", "/" ],

                encode: function( data ) 
                {
                    // Tranform data string to an array for easier handling
                    var input = Array();                
                    for ( var i = 0; i<data.length; i++ ) 
                    {
                        input[i] = data.charCodeAt( i );
                    }

                    var encoded = Array();
                    
                    // Create padding to let us operate on 24 bit ( 3 byte ) chunks till the end
                    var padding = 0;
                    while ( input.length % 3 != 0 ) 
                    {
                        input.push( 0 );
                        padding++;
                    }

                    for( var i=0; i<input.length; i+=3 ) 
                    {
                        encoded.push( Base64.charset[ input[i] >> 2 ] );
                        encoded.push( Base64.charset[ ( ( input[i] & 3) << 4 ) | ( input[i+1] >> 4 ) ] );
                        encoded.push( Base64.charset[ ( ( input[i+1] & 15) << 2 ) | ( input[i+2] >> 6 ) ] );
                        encoded.push( Base64.charset[ ( input[i+2] & 63 ) ] );
                    }


                    // Replace our added zeros with the correct padding characters
                    for( var i=0; i<padding; i++ ) 
                    {
                        encoded[encoded.length-1-i]= "=";                    
                    }

                    return encoded.join( "" );
                }
            }

            // Register our new onload function
            document.addEventListener(  "DOMContentLoaded", Image3D.init, false );
EOF;
    }

    /**
     * Save all the gathered information to a html file
     * 
     * @param string $file File to write output to
     * @return void
     */
    public function save( $file ) 
    {
        file_put_contents( $file, $this->_getJs() );
    }

    /**
     * Return the shading methods this output driver is capable of
     *
     * @return array Shading methods supported by this driver
     */
    public function getSupportedShading() 
    {
        return array( Image_3D_Renderer::SHADE_NO, Image_3D_Renderer::SHADE_FLAT);//, Image_3D_Renderer::SHADE_GAUROUD );
    }
}

?>
