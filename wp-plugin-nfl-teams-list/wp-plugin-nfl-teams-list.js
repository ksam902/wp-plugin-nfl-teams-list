(function() {
    $(document).ready( function () {

    	if ($('#nfl_teams_list_table').length) {
	        $('#nfl_teams_list_table').DataTable({
	        	"info":     false // disabling info - example: "Showing x of y results"
	        });
	    }  
    } );
})();