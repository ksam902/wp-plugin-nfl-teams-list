<?php
/**
 * Plugin Name: NFL Teams List
 * Version: 1.0
 * Author: Kyle Samson
 * Description: A plugin that displays a list of NFL teams in a datatable. The datatable is rendered using a shortcode.
 * License: GPL2
*/

class NFL_Teams_List_Plugin {

    private $api_key;
    private $api_response;

    /**
    * Constructor. Called when the plugin is initialised.
    */
    function __construct($api_response = array()) {
        add_action('wp_enqueue_scripts', array(  $this, 'nfl_teams_list_scripts'));
        add_action( 'admin_init', array(  $this, 'nfl_teams_list_settings_init' ));
        add_action( 'admin_menu', array(  $this, 'nfl_teams_list_options_page' ));
        add_shortcode('nfl_listing', array(  $this, 'shortcode_nfl_listing'));

        $this->api_key = get_option('nfl_listing_settings_api_key');
        $this->api_response = $api_response;
    }

    function nfl_teams_list_scripts() {
        // wp_register_style( 'namespace', 'http://locationofcss.com/mycss.css' );
        // wp_enqueue_style( 'namespace' );

        wp_enqueue_style( 'nfl_teams_list_jquery' );
        wp_enqueue_script( 'nfl_teams_list_jquery', 'https://code.jquery.com/jquery-3.4.1.min.js', array( 'jquery' ) );

        wp_register_style( 'nfl_teams_list_datatables_css', 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css' );
        wp_enqueue_style( 'nfl_teams_list_datatables_css' );

        wp_register_script( 'nfl_teams_list_datatables_js', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js');
        wp_enqueue_script('nfl_teams_list_datatables_js');

        wp_register_script( 'nfl_teams_list_script', plugins_url('wp-plugin-nfl-teams-list.js',__FILE__ ));
        wp_enqueue_script('nfl_teams_list_script');

        $script_params = array(
                   /* examples */
                   'post' => 99,
                   'users' => array( 1, 20, 2049 )
               );

               wp_localize_script( 'nfl_teams_list_script', 'scriptParams', $script_params );
    }


    /**
     * custom option and settings
     */
    function nfl_teams_list_settings_init() {
        // register a new setting for "nfl_teams_list" page
        register_setting( 'nfl_teams_list', 'nfl_teams_list_settings_api_key' );

        // register a new section in the "nfl_teams_list" page
        add_settings_section(
            'nfl_teams_list_setting_section',
            '',
            array( $this, 'nfl_teams_list_setting_section_callback'),
            'nfl_teams_list'
        );

        // register a new field in the "nfl_teams_list_setting_section" section, inside the "nfl_teams_list" page
        add_settings_field(
            'nfl_teams_list_field_api_key', // as of WP 4.6 this value is used only internally
            // use $args' label_for to populate the id inside the callback
            __( 'API Key', 'nfl_teams_list' ),
            array( $this, 'nfl_teams_list_field_api_key_callback'),
            'nfl_teams_list',
            'nfl_teams_list_setting_section',
            [
                'label_for' => 'nfl_teams_list_field_api_key',
                // 'class' => 'nfl_listing_row',
                // 'nfl_listing_custom_data' => 'custom',
            ]
        );
    }
     
    /**
     * custom option and settings:
     * callback functions
     */
     
    // section callbacks can accept an $args parameter, which is an array.
    // $args have the following keys defined: title, id, callback.
    // the values are defined at the add_settings_section() function.
    function nfl_teams_list_setting_section_callback( $args ) {
        ?>
            <p>Please provide an API key to successfully display the list of NFL teams.</p>
        <?php
    }
     
     
    // field callbacks can accept an $args parameter, which is an array.
    // $args is defined at the add_settings_field() function.
    // wordpress has magic interaction with the following keys: label_for, class.
    // the "label_for" key value is used for the "for" attribute of the <label>.
    // the "class" key value is used for the "class" attribute of the <tr> containing the field.
    // you can add custom key value pairs to be used inside your callbacks.
    function nfl_teams_list_field_api_key_callback( $args ) {
        // get the value of the setting we've registered with register_setting()
        $api_key = esc_attr(get_option('nfl_teams_list_settings_api_key', ''));
        // output the field
        ?>
            <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="nfl_teams_list_settings_api_key"  value="<?php echo $api_key; ?>"/>
        <?php
    }
     
    /**
     * Add top level menu item for Plugin Setting page.
     */
    function nfl_teams_list_options_page() {
        add_menu_page(
            'NFL Team List Settings',
            'NFL Team List',
            'manage_options',
            'nfl_teams_list',
            array( $this, 'nfl_teams_list_options_page_html')
        );
    }
     
    /**
     * top level menu:
     * callback functions
     */
    function nfl_teams_list_options_page_html() {
         // check user capabilities
         if ( ! current_user_can( 'manage_options' ) ) {
            return;
         }
         
         // add error/update messages
         
         // check if the user have submitted the settings
         // wordpress will add the "settings-updated" $_GET parameter to the url
         if ( isset( $_GET['settings-updated'] ) ) {
             // add settings saved message with the class of "updated"
             add_settings_error( 'nfl_teams_list_messages', 'nfl_teams_list_message', __( 'Settings Saved', 'nfl_teams_list' ), 'updated' );
         }
         
         // show error/update messages
         settings_errors( 'nfl_teams_list_messages' );
         ?>
             <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                         // output security fields for the registered setting "nfl_teams_list"
                         settings_fields( 'nfl_teams_list' );
                         // output setting sections and their fields
                         // (sections are registered for "nfl_teams_list", each field is registered to a specific section)
                         do_settings_sections( 'nfl_teams_list' );
                         // output save settings button
                         submit_button( 'Save Settings' );
                     ?>
                </form>
             </div>
         <?php
        }



    /**
    * Add custom shortcode used for NFL API Results.
    *
    * @param array $atta array of shortcode properties.
    * @return string Rendered shortcode.
    */
    function shortcode_nfl_listing($atts, $content = null){
        extract( shortcode_atts( array(
            'id'       => 'id',
        ), $atts ) );

        $id = esc_attr($id);



        // var_dump($this->api_response->results->data->team);
        // die('here');

        if (!empty($this->api_key)) {       
            $content = '
                <table id="nfl_listing_table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Nickname</th>
                            <th>Conference</th>  
                            <th>Division</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($this->api_response->results->data->team as $key => $team) {
                    $content .= '<tr>
                        <td>' . $team->display_name . '</td>
                        <td>' . $team->nickname . '</td>
                        <td>' . $team->conference . '</td>
                        <td>' . $team->division . '</td>  
                    </tr>';
                }
            $content .= '</tbody>
                    <tfoot>
                        <tr>
                            <th>Name</th>
                            <th>Nickname</th>
                            <th>Conference</th>
                            <th>Division</th>
                        </tr>
                    </tfoot>
                </table>
            ';
        }else{
            $content = '<div><p>Uh oh! Looks like you\'re missing an API key. Please go to the plugin settings page to update your API key.</p></div>';
        }        
        
        return $content;
    }
}

$request = curl_init('http://delivery.chalk247.com/team_list/NFL.JSON?api_key=74db8efa2a6db279393b433d97c2bc843f8e32b0');                                                                      
curl_setopt($request, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($request, CURLOPT_POSTREDIR, 3);                                                                  
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);                                 
curl_setopt($request, CURLOPT_FOLLOWLOCATION, true); // follow http 3xx redirects
$response = curl_exec($request); // execute
$response = json_decode($response);
  
$nfl_teams_list = new NFL_Teams_List_Plugin($response);

