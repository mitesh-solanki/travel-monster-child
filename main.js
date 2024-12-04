jQuery(document).ready(function () {
    jQuery("#wp-travel-engine-order-form").submit(function (e) {
      e.preventDefault();
      var formData = jQuery(this).serializeArray(); // Collect form data
      let bookingId = jQuery(this).attr("data-id");
      let wte_id = jQuery(".cpm_wte_id").val();
      let home_url = jQuery(".cpm_home_url").val();
      /**
       * Form validation starts
       */
      var hasEmptyFields = false;
      var formDataObj = {};
  
      // Convert formData array into an object for easier access
      jQuery.each(formData, function (index, field) {
        formDataObj[field.name] = field.value.trim();
      });
  
      // Define the specific fields to check
      var fieldsToCheck = ["title", "fname", "wp_travel_engine_placeorder_setting-place_order-travelers-dob", "country"];
  
      // Iterate over formData to find and check specific traveler-related fields dynamically
      jQuery.each(formDataObj, function (name, value) {
        fieldsToCheck.forEach(function (field) {
          var regex = new RegExp(
            `wp_travel_engine_placeorder_setting\\[place_order\\]\\[travelers\\]\\[${field}\\]\\[\\d+\\]`
          );
          if (regex.test(name) && value === "") {
            hasEmptyFields = true;
          }
        });
      });
  
      // If there are empty fields, prevent form submission or handle it accordingly
      if (hasEmptyFields) {
        return; // Prevent form submission
      }
      /**
       * Form validation ends
       */
  
      jQuery.ajax({
        url: ajaxUrl.ajax_url, // WordPress provides this variable for AJAX calls
        type: "POST",
        data: {
          action: "submit_traveler_information",
          form_data: formData,
          booking_id: bookingId,
        }, // Send the form data along with an action identifier
        success: function (response) {
          let newUrl = home_url + "/checkout/?wte_id=" + encodeURIComponent(wte_id) + "&bookingId=" + encodeURIComponent(bookingId);
  
          // Redirect to the new URL
          window.location.href = newUrl;
        },
        error: function () {
          console.error("AJAX request failed");
        },
      });
    });
  });
  