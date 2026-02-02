jQuery(function ($) {
  /**
   * Load dropdown options via AJAX
   */
  function loadDropdown($select) {
    // 🔥 read saved value from HTML attribute
    const currentValue = $select.attr("data-selected") || "";

    // collect selected values from OTHER dropdowns
    const selectedValues = $(".composite-select")
      .not($select)
      .map(function () {
        return $(this).attr("data-selected");
      })
      .get()
      .filter(Boolean);

    $.post(
      wcComposite.ajaxUrl,
      {
        action: "get_composite_products",
        exclude: selectedValues,
        current_product_id: wcComposite.postId,
      },
      function (res) {
        if (!res.success) return;

        // build options
        $select.html(res.data);

        // 🔥 restore selected value AFTER options exist
        if (currentValue) {
          $select.val(String(currentValue));
        }
      },
    );
  }

  /* ---------------------------------
   * INIT EXISTING ROWS (IMPORTANT)
   * --------------------------------- */
  $(".composite-select").each(function () {
    loadDropdown($(this));
  });

  /* ---------------------------------
   * ADD NEW DROPDOWN
   * --------------------------------- */
  $("#add-component").on("click", function () {
    const row = $(`
      <div class="composite-row">
        <select class="composite-select"
                name="composite_components[]"
                data-selected="">
          <option value="">Loading...</option>
        </select>
        <button type="button" class="button remove-component">➖</button>
      </div>
    `);

    $("#composite-wrapper").append(row);

    loadDropdown(row.find(".composite-select"));
  });

  /* ---------------------------------
   * REMOVE DROPDOWN
   * --------------------------------- */
  $(document).on("click", ".remove-component", function () {
    $(this).closest(".composite-row").remove();

    $(".composite-select").each(function () {
      loadDropdown($(this));
    });
  });

  /* ---------------------------------
   * CHANGE HANDLER (SINGLE SOURCE OF TRUTH)
   * --------------------------------- */
  $(document).on("change", ".composite-select", function () {
    const $select = $(this);
    const value = $select.val() || "";

    // 🔥 persist selection back to HTML
    $select.attr("data-selected", value);

    // refresh all dropdowns
    $(".composite-select").each(function () {
      loadDropdown($(this));
    });
  });
});
