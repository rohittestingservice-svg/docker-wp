jQuery(function ($) {
  $(".toggle-options").on("click", function () {
    $(this).closest(".composite-item").find(".option-wrapper").slideToggle(200);
  });
});
