
var random_generator = random_generator || {};

(function($){

    /**
     * Returns a random integer between min (inclusive) and max (inclusive)
     * Using Math.round() will give you a non-uniform distribution!
     *
     *http://stackoverflow.com/questions/1527803/generating-random-whole-numbers-in-javascript-in-a-specific-range
     *
     */
    function random( min, max ) {
        return Math.floor( Math.random() * ( max - min + 1 ) ) + min;
    }

    /*
     * Return the results of multiple random numbers added together,
     * emulating dice rolls.
     */
    function rollDice( no, die ) {
        var result = 0;

        for ( var i = 0; i < no; i++ ) {
            result = result + random( 1, die );
        }

        return result;
    }

    /*
     * Random entity chooses a random item from an array
     */
    function randomItem( items ) {
        var item = items[ random( 0, items.length-1 ) ];

        return item;
    }

    /*
     * Rolls an entity's dice and combines with entity description
     */
    function getItemResult( item )  {

        var result = item.text;

        if ( item.no && item.die ) {
            var count = rollDice( item.no, item.die );

            if (item.show_ones || count !== 1) {
                result = count + ' ' + result;
            }

            if (item.details) {
                result += " " + randomItem(item.details);
            }
        }

        return result;
    }

    /*
     * Loop through .js random_generator tokens to build new description based on random results
     */
    function rollButtonClick() {
        var output = random_generator.description;

        $.each( random_generator.token_groups, function( index, group ) {
            var item = randomItem( group.replacements );
            var description = getItemResult( item );
            output = output.replace( group.token, description);
        });

        $( "#random-generator-target" ).html( output );
    }

    /*
     * Initialize script when page is loaded
     */
    $( document ).ready( function() {
        $( "#random-generator-button" ).click( rollButtonClick );
    });
})(jQuery);