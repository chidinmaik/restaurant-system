import $ from "jquery"
import * as bootstrap from "bootstrap"

// Document ready
$(document).ready(function() {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Toggle sidebar on mobile
  $("#sidebarToggle").on("click", function() {
    $(".sidebar").toggleClass("show");
  });
});