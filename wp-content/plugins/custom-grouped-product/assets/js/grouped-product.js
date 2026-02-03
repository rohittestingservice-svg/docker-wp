jQuery(document).ready(function ($) {
  $(".product-top").on("click", function () {
    $(this).closest(".product-wrapper").toggleClass("active");
  });
});
