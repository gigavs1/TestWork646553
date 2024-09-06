<?php
// Register Cities Custom Post Type
function cities_init() {
    register_post_type('cities', array(
        'labels' => array(
            'name' => 'Cities',
            'singular_name' => 'City',
            'add_new' => 'Add New City',
            'add_new_item' => 'Add New City'
        ),
        'public' => true,
        'show_ui' => true,
        'menu_icon' => 'dashicons-location-alt',
        'supports' => array('title'),
        'rewrite' => array('slug' => 'cities'),
        'query_var' => true
    ));
}
add_action('init', 'cities_init');

// Add City Meta Box
function add_city_meta_box() {
    add_meta_box('city_meta_box', 'City Details', 'display_city_meta_box', 'cities', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_city_meta_box');

// Display City Meta Box Fields
function display_city_meta_box($post) {
    $latitude = get_post_meta($post->ID, '_city_latitude', true);
    $longitude = get_post_meta($post->ID, '_city_longitude', true);
    ?>
    <label for="city_latitude">Latitude: </label>
    <input type="text" id="city_latitude" name="city_latitude" value="<?php echo esc_attr($latitude); ?>" size="25" /><br/><br/>
    <label for="city_longitude">Longitude: </label>
    <input type="text" id="city_longitude" name="city_longitude" value="<?php echo esc_attr($longitude); ?>" size="25" />
    <?php
}

// Save City Meta Data
function save_city_meta_box_data($post_id) {
    if (isset($_POST['city_latitude']) && isset($_POST['city_longitude'])) {
        update_post_meta($post_id, '_city_latitude', sanitize_text_field($_POST['city_latitude']));
        update_post_meta($post_id, '_city_longitude', sanitize_text_field($_POST['city_longitude']));
    }
}
add_action('save_post', 'save_city_meta_box_data');

// Create Countries Taxonomy
function create_countries_taxonomy() {
    register_taxonomy('country', 'cities', array(
        'labels' => array(
            'name' => 'Countries',
            'singular_name' => 'Country',
            'add_new_item' => 'Add New Country',
            'new_item_name' => 'New Country Name'
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'country')
    ));
}
add_action('init', 'create_countries_taxonomy');

// Register the widget using an anonymous function
add_action('widgets_init', function() {
    register_widget('City_Weather_Widget');
});

// Define the widget class
class City_Weather_Widget extends WP_Widget {
    
    // Constructor
    function __construct() {
        parent::__construct(
            'city_weather_widget',
            __('City Weather Widget', 'text_domain'),
            array('description' => __('Displays city name and current temperature.', 'text_domain'))
        );
    }

    // Fetch weather data by city name
    private function fetch_weather_data($city_name) {
        // Secure API key retrieval
        $api_key = defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : '';
        if (empty($api_key)) {
            return null; // If API key is not defined, return null
        }

        // Fetch weather data from OpenWeather API
        $weather_api_url = "https://api.openweathermap.org/data/2.5/weather?q={$city_name}&units=metric&appid={$api_key}";
        $response = wp_remote_get($weather_api_url);

        // Log the API URL for debugging purposes
        error_log("Weather API URL: " . $weather_api_url);

        // Check for errors in the API request
        if (is_wp_error($response)) {
            error_log('Weather API request failed: ' . $response->get_error_message());
            return null;
        }

        // Retrieve and decode the body of the API response
        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }

    // Widget output
    public function widget($args, $instance) {
        $city_name = !empty($instance['city_name']) ? sanitize_text_field($instance['city_name']) : '';

        if ($city_name) {
            $weather_data = $this->fetch_weather_data($city_name);

            // Check if the API returned valid weather data
            if ($weather_data && isset($weather_data->main->temp)) {
                $temperature = $weather_data->main->temp;
                $city_name = $weather_data->name;

                // Display the temperature in the widget
                echo $args['before_widget'];
                echo $args['before_title'] . esc_html($city_name) . $args['after_title'];
                echo '<p>Temperature: ' . esc_html($temperature) . '°C</p>';
                echo $args['after_widget'];
            } else {
                // If city not found or API error
                echo $args['before_widget'];
                echo $args['before_title'] . 'City not found' . $args['after_title'];
                echo '<p>No data available for the specified city.</p>';
                echo $args['after_widget'];
            }
        } else {
            // If no city name is provided
            echo $args['before_widget'];
            echo $args['before_title'] . 'City not specified' . $args['after_title'];
            echo '<p>Please provide a city name.</p>';
            echo $args['after_widget'];
        }
    }

    // Widget backend form
    public function form($instance) {
    $selected_city = !empty($instance['city_name']) ? $instance['city_name'] : '';

    // Fetch cities from the 'Cities' custom post type
    $cities = get_posts(array(
        'post_type' => 'cities',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));

    // Display dropdown for cities
    ?>
    <p>
        <label for="<?php echo $this->get_field_id('city_name'); ?>"><?php _e('Select City:'); ?></label>
        <select class="widefat" id="<?php echo $this->get_field_id('city_name'); ?>" name="<?php echo $this->get_field_name('city_name'); ?>">
            <option value=""><?php _e('Select a City', 'text_domain'); ?></option>
            <?php foreach ($cities as $city): ?>
                <option value="<?php echo esc_attr($city->post_title); ?>" <?php selected($selected_city, $city->post_title); ?>>
                    <?php echo esc_html($city->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
    }

    // Update widget settings
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['city_name'] = (!empty($new_instance['city_name'])) ? sanitize_text_field($new_instance['city_name']) : '';
        return $instance;
    }
}

// Register custom page template for cities weather table
function add_custom_page_template($templates) {
    $templates['template-cities-weather.php'] = 'Cities Weather Table';
    return $templates;
}
add_filter('theme_page_templates', 'add_custom_page_template');

// Load the custom template file
function load_custom_page_template($template) {
    if (is_page_template('template-cities-weather.php')) {
        $template = get_template_directory() . '/template-cities-weather.php';
    }
    return $template;
}
add_filter('template_include', 'load_custom_page_template');

// Fetch cities and weather data from database
function get_cities_weather_data($search = '') {
    global $wpdb;
    $search_query = !empty($search) ? $wpdb->prepare("
        AND (p.post_title LIKE %s OR t.name LIKE %s)", 
        $wpdb->esc_like($search) . '%', 
        $wpdb->esc_like($search) . '%') : '';

    $query = "
        SELECT p.ID, p.post_title as city_name, pm_lat.meta_value as latitude, pm_lon.meta_value as longitude, t.name as country
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = '_city_latitude'
        INNER JOIN {$wpdb->postmeta} pm_lon ON p.ID = pm_lon.post_id AND pm_lon.meta_key = '_city_longitude'
        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'country'
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        WHERE p.post_type = 'cities' AND p.post_status = 'publish'
        $search_query";

    return $wpdb->get_results($query);
}

// Handle AJAX request for city weather data
function fetch_city_weather_ajax() {
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $cities_data = get_cities_weather_data($search);

    if (!empty($cities_data)) {
        echo '<table>
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>City</th>
                        <th>Temperature</th>
                    </tr>
                </thead>
             <tbody>';
        foreach ($cities_data as $city) {
            $temperature = fetch_temperature_for_city($city->city_name);
            echo'<tr>
                    <td>' . esc_html($city->country) . '</td>
                    <td>' . esc_html($city->city_name) . '</td>
                    <td>' . esc_html($temperature) . '°C</td>
                </tr>';
        }
        echo '</tbody>
            </table>';
    } else {
        echo '<p>No countries or cities found.</p>';
    }

    wp_die();
}
add_action('wp_ajax_fetch_city_weather', 'fetch_city_weather_ajax');
add_action('wp_ajax_nopriv_fetch_city_weather', 'fetch_city_weather_ajax');

// Fetch temperature from OpenWeather API
function fetch_temperature_for_city($city_name) {
    $api_key = defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : '';
    if (empty($api_key)) return 'N/A';

    $response = wp_remote_get("https://api.openweathermap.org/data/2.5/weather?q={$city_name}&units=metric&appid={$api_key}");
    if (is_wp_error($response)) return 'Error';

    $weather_data = json_decode(wp_remote_retrieve_body($response));
    return isset($weather_data->main->temp) ? $weather_data->main->temp : 'N/A';
}

// Enqueue script for city weather search
function enqueue_city_weather_script() {
    wp_enqueue_script('city-weather-script', get_template_directory_uri() . '/assets/js/city-weather.js', array('jquery'), null, true);
    wp_localize_script('city-weather-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_city_weather_script');

/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';

if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */
