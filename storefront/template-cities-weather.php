<?php
/*
Template Name: Cities Weather Table
*/

// Include the header
get_header();

// Trigger the custom action hook before the table
do_action('before_cities_weather_table');
?>

<div class="city-weather-search" style="padding: 20px;">
    <label for="city-search">Search Cities:</label>
    <input type="text" id="city-search" placeholder="Enter Keyword">
</div>

<div id="city-weather-table">
    <!-- Table will be dynamically loaded here via AJAX -->
</div>

<?php
// Trigger the custom action hook after the table
do_action('after_cities_weather_table');

// Include the footer
get_footer();
?>
