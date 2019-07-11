(function() {
    console.log('ABC');

    $(document).ready( function () {
        $('#nfl_listing_table').DataTable({
	        "processing": true,
	        "ajax": "https://reqres.in/api/users",
	        "columns": [
	            { "data": "id" },
	            { "data": "first_name" },
	            { "data": "last_name" },
	            { "data": "email" }
	        ]
	    } );

    } );

})();