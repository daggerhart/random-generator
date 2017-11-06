
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
     * @todo - implement item details?
     * Rolls an entity's dice and combines with entity description
     */
    function getEntityResult( entity )  {

        var result = entity.desc;

        if ( entity.no && entity.die ) {
            result = rollDice( entity.no, entity.die )  + " " + entity.desc;
            result += getEntityDetails( entity );
        }

        return result;
    }

    /*
     * For retreiving heirarchical details for a top-level entity
     */
    function getEntityDetails( entity ) {

        var output = "";

        if ( entity.details ) {
            $.each( entity.details, function( key, value ){

                var item = randomItem( value );

                output += " " + random_generator[key][item];
            })
        }
        return output;
    }

    /*
     * Loop through .js random_generator tokens to build new description based on random results
     */
    function rollButtonClick() {
        var output = random_generator.description;

        $.each( random_generator.token_groups, function( index, group ) {
            var item = randomItem( group.replacements );
            var description = getEntityResult( item );
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