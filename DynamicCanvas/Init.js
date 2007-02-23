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
var polygones = %polygones%
// Data section *end*
