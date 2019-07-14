<?php
/**
 * Plugin Name: NFL Teams List
 * Version: 1.0
 * Author: Kyle Samson
 * Description: A WordPress plugin that displays a list of NFL teams in a Datatable using a custom shortcode.
*/

class NFL_Teams_List_Plugin {

    private $api_key;
    private $css;

    /**
    * Constructor. Called when the plugin is initialised.
    */
    function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'nfl_teams_list_scripts'));// Load assets
        add_action('admin_init', array($this, 'nfl_teams_list_settings_init'));// Create Settings section
        add_action('admin_menu', array($this, 'nfl_teams_list_options_page'));// Create Options page
        add_shortcode('nfl_teams_list', array($this, 'shortcode_nfl_teams_list'));// Register custom shortcode

        $this->api_key = get_option('nfl_teams_list_settings_api_key');
        $this->css = get_option('nfl_teams_list_settings_css');
    }

    /**
     * Register & Enqueue Assets
     */
    function nfl_teams_list_scripts() {   
        // jQuery
        wp_enqueue_style('nfl_teams_list_jquery');
        wp_enqueue_script('nfl_teams_list_jquery', 'https://code.jquery.com/jquery-3.3.1.js', array('jquery') );
        // jQuery Datatables
        wp_register_script('nfl_teams_list_datatables_js', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js');
        wp_enqueue_script('nfl_teams_list_datatables_js');
        
        if ($this->css === 'bootstrap') { // only register Bootstrap assets if Bootstrap CSS option has been chosen. 
            // Bootstrap CSS
            wp_register_style('nfl_teams_list_bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css');
            wp_enqueue_style('nfl_teams_list_bootstrap');
            // Datatables/Bootstrap CSS
            wp_register_style('nfl_teams_list_bootstrap_datatables', 'https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css');
            wp_enqueue_style('nfl_teams_list_bootstrap_datatables');

            wp_register_script('nfl_teams_list_bootstrap_js', 'https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js');
            wp_enqueue_script('nfl_teams_list_bootstrap_js');
        }else{
            // Datatables CSS
            wp_register_style('nfl_teams_list_datatables_css', 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css');
            wp_enqueue_style('nfl_teams_list_datatables_css');
        }

        // Custom CSS
        wp_register_style('nfl_teams_list_style_css', plugins_url('wp-plugin-nfl-teams-list.css',__FILE__ ) );
        wp_enqueue_style('nfl_teams_list_style_css');
        // Custom JS file
        wp_register_script('nfl_teams_list_script_js', plugins_url('wp-plugin-nfl-teams-list.js',__FILE__ ));
        wp_enqueue_script('nfl_teams_list_script_js');
    }

    /**
     * Plugin Settings and Sections
     */
    function nfl_teams_list_settings_init() {
        // register new settings and a new section for "nfl_teams_list" page
        register_setting('nfl_teams_list', 'nfl_teams_list_settings_api_key');
        register_setting('nfl_teams_list', 'nfl_teams_list_settings_css');

        add_settings_section(
            'nfl_teams_list_setting_section',
            '',
            array($this, 'nfl_teams_list_setting_section_callback'),
            'nfl_teams_list'
        );

        // register a new field in the "nfl_teams_list_setting_section" section, inside the "nfl_teams_list" page
        add_settings_field(
            'nfl_teams_list_field_api_key',
            __('API Key', 'nfl_teams_list'),
            array($this, 'nfl_teams_list_field_api_key_callback'),
            'nfl_teams_list',
            'nfl_teams_list_setting_section',
            [ 'label_for' => 'nfl_teams_list_field_api_key' ]
        );

        // register a new field in the "nfl_teams_list_setting_section" section, inside the "nfl_teams_list" page
        add_settings_field(
            'nfl_teams_list_field_css', 
            __('CSS', 'nfl_teams_list'),
            array($this, 'nfl_teams_list_field_css_callback'),
            'nfl_teams_list',
            'nfl_teams_list_setting_section',
            ['label_for' => 'nfl_teams_list_field_css']
        );
    }
     
    /**
     * Add helper text to Settings Section.
     */
    function nfl_teams_list_setting_section_callback() {
        ?>
            <p>Please provide an API key to successfully display the list of NFL teams.</p>
        <?php
    }
     
    /**
     * Add API key input Settings field.
     */
    function nfl_teams_list_field_api_key_callback( $args ) {
        $api_key = esc_attr(get_option('nfl_teams_list_settings_api_key', ''));// get stored api key value.
        ?>
            <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="nfl_teams_list_settings_api_key"  value="<?php echo $api_key; ?>"/>
        <?php
    }

    /**
     * Add CSS select Settings field.
     */
    function nfl_teams_list_field_css_callback( $args ) {
        $css = get_option('nfl_teams_list_settings_css');// get stored css value.
        ?>
            <select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="nfl_teams_list_settings_css">
                <option value="default" <?php echo isset( $css ) ? ( selected( $css, 'default', false ) ) : (''); ?>>
                    <?php esc_html_e('Default', 'nfl_teams_list'); ?>
                </option>
                <option value="bootstrap" <?php echo isset( $css ) ? ( selected( $css, 'bootstrap', false ) ) : (''); ?>>
                    <?php esc_html_e('Bootstrap', 'nfl_teams_list'); ?>
                </option>
            </select>
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
        if ( ! current_user_can('manage_options') ) {
            return;
        }

        // check if settings have been submitted - display feedback if so.
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error('nfl_teams_list_messages', 'nfl_teams_list_message', __('Settings Saved', 'nfl_teams_list'), 'updated');
        }
        settings_errors('nfl_teams_list_messages');

        ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                    <?php
                        settings_fields('nfl_teams_list');
                        do_settings_sections('nfl_teams_list');
                        submit_button('Save Settings');
                     ?>
                </form>
            </div>
        <?php
    }

    /**
    * Add custom shortcode used for NFL API Response.
    *
    * @param array $atta array of shortcode properties.
    * @return string Rendered shortcode.
    */
    function shortcode_nfl_teams_list($atts, $content = null){
        // Get shortcode paramters
        // **ASSUMPTION** : Users will not insert special characters into the shortcode parameters.
        extract( shortcode_atts( array(
            'title'       => 'title',
            'subtitle'    => 'subtitle'
        ), $atts ) );

        $title = esc_attr($title);
        $subtitle = esc_attr($subtitle);

        if (!empty($this->api_key)) {       
            $available_css = array(
                'default' => array(
                    'div-class'     =>  'nfl-listing-div',
                    'table-class'   =>  'nfl-listing-table',
                    'thead-class'   =>  'nfl-listing-thead',
                    'tbody-class'   =>  'nfl-listing-tbody'
                ),
                'bootstrap' => array(
                    'div-class' => 'table-responsive',
                    'table-class'   =>  'table table-hover table-borderless',
                    'thead-class'   =>  'thead-light',
                    'tbody-class'   =>  ''
                )
            );
            // Use $css to apply to correct table styling based on the value of CSS in the plugin Settings page ($this->css) 
            $css = $available_css[$this->css];

            // Fetch the NFL teams using the provided API URL
            $request = curl_init('http://delivery.chalk247.com/team_list/NFL.JSON?api_key=' . $this->api_key);                                                                      
            curl_setopt($request, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($request, CURLOPT_ENCODING, '');
            curl_setopt($request, CURLOPT_POSTREDIR, 3);                                                                  
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);                                 
            curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($request); 
            $response = json_decode($response);

            // Building table.
            // **ASSUMPTION** : $response will always return results. Would implement a fallback if this was not the case.
            $content = '
                <div class="base ' . $css['div-class'] . '">' 
                    . (!empty($title) ? '<h4>' . $title . '</h4>' : '') 
                    . (!empty($subtitle) ? '<p><small>' . $subtitle . '</small></p>' : '') 
                    . '<table id="nfl_teams_list_table" class="' . $css['table-class'] . '">
                        <thead class="' . $css['thead-class'] . '">
                            <tr>
                                <th>Name</th>
                                <th>Nickname</th>
                                <th>Conference</th>  
                                <th>Division</th>
                            </tr>
                        </thead>
                    <tbody class="' . $css['tbody-class'] . '">';

            foreach ($response->results->data->team as $key => $team) {
                $content .= '<tr>
                    <td>' . $team->display_name . '</td>
                    <td>' . $team->nickname . '</td>
                    <td>' . $team->conference . '</td>
                    <td>' . $team->division . '</td>  
                </tr>';
            }

            $content .= '</tbody>
                </table>
            </div>';
        }else{
            // Handle situation when no API key is provided.
            $content = '<div><p>Uh oh! Looks like you\'re missing an NFL Teams List API key. Please go to the plugin settings page to update your settings.</p></div>';
        }        

        return $content;
    }
}
  
$nfl_teams_list = new NFL_Teams_List_Plugin();