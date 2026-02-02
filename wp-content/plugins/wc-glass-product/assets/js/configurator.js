document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("gc-container");
  const addBtn = container.querySelector(".gc-add-more");
  const addToCartBtn = document.querySelector("#gc-add-to-cart");

  let stepCount = 1;
  let stepSelections = [];

  // ✅ Helper: Format value
  function formatValue(val) {
    return val
      .replace(/[-_]+/g, " ")
      .trim()
      .replace(/\b\w/g, (c) => c.toUpperCase());
  }

  /**
   * ✅ Attach event listeners to form
   */
  function attachListeners(stepForm) {
    const widthInput = stepForm.querySelector(".width");
    const heightInput = stepForm.querySelector(".height");

    // Add error spans if missing
    [widthInput, heightInput].forEach((input) => {
      if (
        !input.nextElementSibling ||
        !input.nextElementSibling.classList.contains("error-message")
      ) {
        const error = document.createElement("span");
        error.className = "error-message";
        input.insertAdjacentElement("afterend", error);
      }
    });

    /* ============================================
     * ENABLE RADIO UNCHECK ON SECOND CLICK
     * ============================================ */
    stepForm.querySelectorAll('input[type="radio"]').forEach((radio) => {
      radio.addEventListener("mousedown", function () {
        // Store whether it was checked BEFORE click
        this.wasChecked = this.checked;
      });

      radio.addEventListener("click", function (e) {
        // If previously checked → uncheck it manually
        if (this.wasChecked) {
          e.preventDefault();
          this.checked = false;

          // Trigger "change" so your price update runs
          this.dispatchEvent(new Event("change", { bubbles: true }));
        }
      });
    });

    /* ============================================
     * YOUR ORIGINAL CHANGE LISTENER
     * ============================================ */
    stepForm.querySelectorAll("input, select").forEach((el) => {
      el.addEventListener("change", (e) => {
        /* ------------------------------------------
         *  Your original validation logic
         * ------------------------------------------ */

        const width = parseFloat(widthInput.value) || 0;
        const height = parseFloat(heightInput.value) || 0;
        const typeSelect = stepForm.querySelector(".glass_type");
        const colorSelect = stepForm.querySelector(".glass_color");

        const type = typeSelect?.value.trim() || "";
        const color = colorSelect?.value.trim() || "";

        const widthError = stepForm.querySelector(".width + .error-message");
        const heightError = stepForm.querySelector(".height + .error-message");

        // === Width Validation ===
        if (e.target.classList.contains("width")) {
          widthError.textContent =
            width < 1500 || width > 7000
              ? "Width must be between 1500mm and 7000mm."
              : "";
        }

        // === Height Validation ===
        if (e.target.classList.contains("height")) {
          heightError.textContent =
            height < 1700 || height > 3000
              ? "Height must be between 1700mm and 3000mm."
              : "";
        }

        /* ------------------------------------------
         *  Collect Form Data
         * ------------------------------------------ */
        const formData = {};

        stepForm.querySelectorAll("input, select").forEach((input) => {
          const key = input.name;
          if (!key) return;

          // ----- CHECKBOX -----
          if (input.type === "checkbox") {
            if (input.checked) {
              const label =
                input.closest(".option-group")?.querySelector("h3") ||
                input.closest(".input-box")?.querySelector("label");

              const title = label?.textContent.trim() || "";

              formData[key] = { title, value: input.value };
            } else {
              delete formData[key]; // Remove if unchecked
            }
            return;
          }

          // ----- RADIO -----
          if (input.type === "radio") {
            const checked = stepForm.querySelector(
              `input[name="${key}"]:checked`
            );

            if (checked) {
              const radioLabel = checked.closest(".group-label");
              formData[key] = {
                title: radioLabel?.dataset.label,
                value: checked.value,
              };
            } else {
              delete formData[key];
            }

            return;
          }

          // ----- OTHER INPUTS (text, number, select) -----
          const val = input.value.trim();
          if (val === "") {
            delete formData[key];
            return;
          }

          const label = input.closest(".input-box")?.querySelector("label");
          const title = label?.textContent.trim() || "";

          formData[key] = { title, value: val };
        });

        console.log("formData", formData);

        /* ------------------------------------------
         *  AJAX Logic
         * ------------------------------------------ */
        const stepIndex = parseInt(stepForm.dataset.stepIndex) || 0;

        const isWidthValid = width >= 1500 && width <= 7000;
        const isHeightValid = height >= 1700 && height <= 3000;
        const isTypeValid = type !== "";
        const isColorValid = color !== "";

        if (isWidthValid && isHeightValid && isTypeValid && isColorValid) {
          const ajaxData = {
            action: "gc_get_product_data",
            form_data: JSON.stringify(formData),
          };

          fetch(wc_add_to_cart_params.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(ajaxData).toString(),
          })
            .then((res) => res.json())
            .then((data) => {
              if (data.success) {
                addToCartBtn.style.display = "block";
                stepSelections[stepIndex] = data?.data;
                reindex(stepSelections);
              } else {
                console.error(data.data.message);
              }
            })
            .catch((err) => console.error("Error:", err));
        } else {
          addToCartBtn.style.display = "none";
        }
      }); // END CHANGE
    }); // END LOOP

    // ✅ Remove step
    const removeBtn = stepForm.querySelector(".gc-remove");
    if (removeBtn) {
      removeBtn.addEventListener("click", () => {
        const stepIndex = parseInt(stepForm.dataset.stepIndex, 10);
        stepForm.remove();
        if (!isNaN(stepIndex)) stepSelections.splice(stepIndex, 1);
        reindex(stepSelections);
      });
    }
  }

  /**
   * ✅ Add to cart
   */
  addToCartBtn.addEventListener("click", function () {
    console.log("datax", JSON.stringify(stepSelections));
    // return false;
    fetch(gc_params.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "gc_add_to_cart",
        selections: JSON.stringify(stepSelections),
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) window.location.href = wc_add_to_cart_params.cart_url;
        else alert("Failed to add product to cart.");
      });
  });

  /**
   * ✅ Reindex and Preview (fixed order)
   */
  function reindex(stepSelections) {
    const forms = container.querySelectorAll(".gc-step-form");
    forms.forEach((form, i) => {
      form.dataset.stepIndex = i;
      form.querySelector("h5").textContent = "Schuifwand " + (i + 1) + ":";

      const checkboxContainer = form.querySelector(".extra-options");
      checkboxContainer
        .querySelectorAll(".input-box")
        .forEach((item, index) => {
          const input = item.querySelector("input");
          const label = item.querySelector("label");
          if (input && label) {
            const baseName = input.name || "checkbox";
            const newId = `${baseName}_${i}_${index}`;
            input.id = newId;
            label.setAttribute("for", newId);
          }
        });
    });

    const previewWrapper = document.querySelector(".preview-item-wrapper");
    previewWrapper.innerHTML = "";

    // ✅ fixed order for display
    const order = [
      "pa_soort-glas", // Glas type
      "width", // Kaderbreedte
      "height", // Kaderhoogte
      "pa_kleur", // Profielkleur
      "panel", // Glaspanelen
      "overlapping", // Overlap
      "handgreep", // Handgreep
      "tochstrips", // Tochtstrips
      "u_profielen", // U-Profielen
      "meenemers", // Meenemers
      "funderingsbalk", // Funderingsbalk
      "verlengde", // Verlengde
    ];

    stepSelections.forEach((data, i) => {
      if (!data || !Object.keys(data).length) return;

      let { selections, total_amount } = data;
      console.log("selections", typeof selections);
      // return false;
      let html = `
        <div class="gc-preview-item" data-index="${i}" style="display:block;">
          <div class="item-title">
            <h5 class="offer-heading">Glazen schuifwand ${i + 1}</h5>
          </div>
      `;

      // let total = 0;
      Object.keys(selections).forEach((key) => {
        const item = selections[key];
        if (!item || !item.title) return;
        html += `<div class="row-data">
            <strong>${item.title}:</strong>
            <span class="preview-data">${formatValue(item.value) || ""}</span>
            <span class="preview-data price">${item.price || ""}</span>
          </div>`;
      });

      html += `
        <div class="gc-ammount">
          <span>Totaalbedrag</span>
          <span id="gc-total-price">${total_amount}</span>
        </div>
      </div>
      `;

      previewWrapper.insertAdjacentHTML("beforeend", html);
    });
  }

  /**
   * ✅ Add new step form
   */
  addBtn.addEventListener("click", () => {
    stepCount++;
    const firstForm = container.querySelector(".gc-step-form");
    const formClone = firstForm.cloneNode(true);

    formClone.querySelectorAll("input, select").forEach((input) => {
      if (["checkbox", "radio"].includes(input.type)) input.checked = false;
      else input.value = "";
    });

    formClone.querySelectorAll(".error-message").forEach((error) => {
      error.textContent = "";
      error.style.display = "none";
    });

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "gc-remove";
    removeBtn.textContent = "✖";
    formClone.querySelector(".conf_input-wrapper").appendChild(removeBtn);

    removeBtn.addEventListener("click", () => {
      formClone.remove();
      reindex(stepSelections);
    });

    container.querySelector(".gc-left .form-wrapper").appendChild(formClone);
    attachListeners(formClone);
    reindex(stepSelections);
  });

  // ✅ Initialize first form
  const firstForm = container.querySelector(".gc-step-form");
  attachListeners(firstForm);
});
