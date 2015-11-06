var ProfileModifier = (function($){
    var modifier = {};
    
    /**
     * Get a tool tip
     * 
     * @returns jQuery element
     */
    modifier.readOnlyFieldsHelp = function() {
        var helpurl = "/help.php";
        helpurl += "?component=theme_vision";
        helpurl += "&identifier=syncedfield";
        helpurl += "&lang=en_us";
        
        var $image = $("<img>")
            .addClass("iconhelp")
            .attr("alt", "Help with this field")
            .attr("src", M.util.image_url("help"));
    
        var $link = $("<a></a>")
            .attr("target", "_blank")
            .attr("aria-haspopup", "true")
            .attr("title", "Read only field")
            .attr("href", helpurl);
    
        var $span = $("<span></span>").addClass("helptooltip");
        
        return $span.append( $link.append( $image ) );
    };
    
    /**
     * Set the fields to read only
     * 
     * @param YUI yui
     *   This is a reference to YUI. We don't need it, but Moodle passes it
     *   when calling js_init_call, so we have to deal with it.
     * @param array fields
     *   An array of field names
     * @returns void
     */
    modifier.readOnlyFields = function(yui, fields){
        $(document).ready(function(){
            var $help = modifier.readOnlyFieldsHelp();

            for (var i = 0; i < fields.length; i++) {
                // Set the field to read only.
                $("#id_" + fields[i]).attr("readonly", "readonly");
                
                // Add a help button to tell the user why it is read only.
                $("[for=id_"+fields[i]+"]").after($help.clone());
            }
        });
    };
    
    return modifier;
}(jQuery));
