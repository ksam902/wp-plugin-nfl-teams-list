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

    /**
    * Constructor. Called when the plugin is initialised.
    */
    function __construct() {
        add_action('wp_enqueue_scripts', array(  $this, 'nfl_teams_list_scripts'));
        add_action( 'admin_init', array(  $this, 'nfl_teams_list_settings_init' ));
        add_action( 'admin_menu', array(  $this, 'nfl_teams_list_options_page' ));
        add_shortcode('nfl_listing', array(  $this, 'shortcode_nfl_listing'));

        $this->api_key = get_option('nfl_listing_settings_api_key');
    }

    function nfl_teams_list_scripts() {

        // DEV-NOTE : Custom CSS.
        // wp_register_style( 'namespace', 'http://locationofcss.com/mycss.css' );
        // wp_enqueue_style( 'namespace' );

        wp_register_style( 'nfl_teams_list_datatables_css', 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css' );
        wp_enqueue_style( 'nfl_teams_list_datatables_css' );

        wp_enqueue_style( 'nfl_teams_list_jquery' );
        wp_enqueue_script( 'nfl_teams_list_jquery', 'https://code.jquery.com/jquery-3.4.1.min.js', array( 'jquery' ) );

        wp_register_script( 'nfl_teams_list_datatables_js', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js');
        wp_enqueue_script('nfl_teams_list_datatables_js');

        wp_register_script( 'nfl_teams_list_script', plugins_url('wp-plugin-nfl-teams-list.js',__FILE__ ));
        wp_enqueue_script('nfl_teams_list_script');
    }


    /**
     * Plugin Settings and Sections
     */
    function nfl_teams_list_settings_init() {
        // register a new setting and a new section for "nfl_teams_list" page
        register_setting( 'nfl_teams_list', 'nfl_teams_list_settings_api_key' );
        add_settings_section(
            'nfl_teams_list_setting_section',
            '',
            array( $this, 'nfl_teams_list_setting_section_callback'),
            'nfl_teams_list'
        );

        // register a new field in the "nfl_teams_list_setting_section" section, inside the "nfl_teams_list" page
        add_settings_field(
            'nfl_teams_list_field_api_key',
            __( 'API Key', 'nfl_teams_list' ),
            array( $this, 'nfl_teams_list_field_api_key_callback'),
            'nfl_teams_list',
            'nfl_teams_list_setting_section',
            [ 'label_for' => 'nfl_teams_list_field_api_key' ]
        );
    }
     
    /**
     * Add helper text to Settings Section.
     */
    function nfl_teams_list_setting_section_callback( $args ) {
        ?>
            <p>Please provide an API key to successfully display the list of NFL teams.</p>
        <?php
    }
     
     
    /**
     * Add API key input Settings field.
     */
    function nfl_teams_list_field_api_key_callback( $args ) {
        // get stored api key value.
        $api_key = esc_attr(get_option('nfl_teams_list_settings_api_key', '')); 
        ?>
            <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="nfl_teams_list_settings_api_key"  value="<?php echo $api_key; ?>"/>
        <?php
    }
     
    /**
     * Create Top level menu item for the Plugin Settings page.
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
     * Top level menu item callback function
     */
    function nfl_teams_list_options_page_html() {
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // check if settings have been submitted - display feedback if so.
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'nfl_teams_list_messages', 'nfl_teams_list_message', __( 'Settings Saved', 'nfl_teams_list' ), 'updated' );
        }
        settings_errors( 'nfl_teams_list_messages' );

        ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                        settings_fields( 'nfl_teams_list' );
                        do_settings_sections( 'nfl_teams_list' );
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
   
        if (!empty($this->api_key)) {       

            $request = curl_init('http://delivery.chalk247.com/team_list/NFL.JSON?api_key=' . $this->api_key);                                                                      
            curl_setopt($request, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($request, CURLOPT_POSTREDIR, 3);                                                                  
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);                                 
            curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($request); // execute
            $response = json_decode($response);

            // Building table.
            // **ASSUMPTION** : $response will always return results. Would implement a fallback if this was not the case.
            $content = '<table id="nfl_listing_table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Nickname</th>
                        <th>Conference</th>  
                        <th>Division</th>
                    </tr>
                </thead>
            <tbody>';

            foreach ($response->results->data->team as $key => $team) {
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
            </table>';
        }else{
            $content = '<div><p>Uh oh! Looks like you\'re missing an API key. Please go to the plugin settings page to update your API key.</p></div>';
        }        
        
        return $content;
    }
}
  
$nfl_teams_list = new NFL_Teams_List_Plugin();

