# NFL Teams List - WordPress Plugin

A WordPress plugin that displays a list of NFL teams in a Datatable. The Datatable can be included in any page using a custom shortcode. The plugin includes a Settings page where a user can provide the plugin with an API key used to fetch the NFL teams and set the styling for the Datatable.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for testing purposes.

### Installing the Plugin

* Download a zip, or clone this repository.
* Copy the `wp-plugin-nfl-teams-list` directory into the `/wp-content/plugins/` directory of your WordPress installation.
* Activate the `NFL Teams List` plugin.
* Navigate to the `NFL Teams List Settings` page by clicking `NFL Teams List` in the side menu.
* Enter a valid API key (example: `74db8efa2a6db279393b433d97c2bc843f8e32b0`).
* Save.

### Using the Shortcode

To use the custom `nfl_teams_list` shortcode enter `[nfl_teams_list/]` into your content and save your changes. The shortcode also accepts parameters which are listed below.

Valid shortcode parameters:

* `title` (string) - optional string value, if provided a title will appear above the Datatable displaying the provided text. 

## Assumptions

TODO

## Built With

* [Datatables](https://datatables.net/) - jQuery plug-in used to make the table fancy.
* [Bootstrap 4](https://getbootstrap.com/) - toolkit to assist with HTML, CSS and JS.