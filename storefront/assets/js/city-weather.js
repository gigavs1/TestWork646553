jQuery(document).ready(function ($) {
  // Debounce function to limit the number of AJAX calls
  function debounce(func, wait) {
    let timeout;
    return function () {
      const context = this;
      const args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(context, args), wait);
    };
  }

  // Trigger search when typing in the search box with debounce
  $("#city-search").on(
    "keyup",
    debounce(function () {
      var searchValue = $(this).val(); // Get the search term

      // Make the AJAX call
      $.ajax({
        url: ajax_object.ajax_url,
        type: "POST",
        data: {
          action: "fetch_city_weather",
          search: searchValue, // Pass the search term to the server
        },
        success: function (response) {
          $("#city-weather-table").html(response); // Update the table with the response
        },
      });
    }, 400)
  ); // Set the debounce delay (300ms in this case)

  // Trigger the search function when the page loads (to show all cities initially)
  $("#city-search").trigger("keyup");
});
