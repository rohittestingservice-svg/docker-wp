document.addEventListener("DOMContentLoaded", () => {
  //function to update the price
  function updateWooPrice(totalAmount) {
    const priceElement = document.querySelector(
      ".woocommerce-Price-amount.amount bdi"
    );

    if (!priceElement) return;

    // Get the currency symbol dynamically
    const currencySymbol =
      document.querySelector(".woocommerce-Price-currencySymbol")
        ?.textContent || "";

    // Update the displayed price
    priceElement.innerHTML = `<span class="woocommerce-Price-currencySymbol">${currencySymbol}</span>${totalAmount.toFixed(
      2
    )}`;
  }

  // Calculate the price
  function calculatePrice() {
    let selections = {};
    const form = document.querySelector("form.cart");

    // Safely get edit_key
    const item_key =
      form?.querySelector('input[name="edit_key"]')?.value || null;

    // Get product price
    let totalPrice = parseFloat(
      form?.querySelector('input[name="product_price"]')?.value || 0
    );

    // Collect hidden fields inside .cart-items-data
    const cartDataDiv = document.querySelector(".cart-items-data");

    if (cartDataDiv) {
      const hiddenInputs = cartDataDiv.querySelectorAll('input[type="hidden"]');

      hiddenInputs.forEach((element) => {
        const key = element.name?.trim();
        if (!key) return;

        const title = element.dataset.title || key;
        const value = element.value || "";
        const price = parseFloat(element.dataset.price) || 0;
        selections[key] = { title, value, price };

        // skip overlapping price to add in total price
        if (key !== "overlapping") {
          totalPrice += price;
        }
      });
    }

    // Update the price on the page
    updateWooPrice(totalPrice);

    return { totalPrice, selections, item_key };
  }

  //steps events
  const stepBuilders = Array.from(document.querySelectorAll("#step-builder"));
  const cartItemsContainer = document.querySelector(".cart-items-data");
  stepBuilders.forEach((stepBuilder) => {
    const steps = Array.from(stepBuilder.querySelectorAll(".step"));
    if (!steps.length) {
      console.warn("No steps found for this builder");
      return;
    }

    // calculate the price when page loaded
    const { totalPrice, selections } = calculatePrice(steps);
    console.log(totalPrice, selections);

    //click event
    steps.forEach((step, index) => {
      // Radios
      const radios = step.querySelectorAll("input[type='radio']");
      radios.forEach((radioBtn) => {
        radioBtn.addEventListener("change", (e) => {
          const name = e.target.name;
          const title = e.target.dataset.title || "";
          const value = e.target.value;
          const price = e.target.dataset.price || 0; // ✅ get from the radio button itself

          // Try to find an existing input by name
          let existingInput = cartItemsContainer.querySelector(
            `input[name="${name}"]`
          );

          if (existingInput) {
            // ✅ Update the existing input
            existingInput.value = value;
            existingInput.dataset.title = title;
            existingInput.dataset.price = price;
            console.log(`Updated input: ${name} = ${value}`);
          } else {
            // ✅ Create a new hidden input if not found
            const newInput = document.createElement("input");
            newInput.type = "hidden";
            newInput.name = name;
            newInput.value = value;
            newInput.dataset.title = title;
            newInput.dataset.price = price;

            cartItemsContainer.appendChild(newInput);
            console.log(`Created new input: ${name} = ${value}`);
          }

          // ✅ Recalculate total or other dependent values
          calculatePrice();
        });
      });

      // checkboxs
      const checkboxs = step.querySelectorAll("input[type='checkbox']");
      checkboxs.forEach((checkboxBtn) => {
        checkboxBtn.addEventListener("change", (e) => {
          const checkbox = e.target;
          const name = checkbox.name;
          const title = checkbox.dataset.title || "";
          const value = checkbox.value || "";
          const price = checkbox.dataset.price || 0;

          // Try to find existing hidden input
          let existingInput = cartItemsContainer.querySelector(
            `input[name="${name}"]`
          );

          if (checkbox.checked) {
            // ===============================
            // ✅ IF CHECKED → Add/Update
            // ===============================
            if (existingInput) {
              existingInput.value = value;
              existingInput.dataset.title = title;
              existingInput.dataset.price = price;
            } else {
              const newInput = document.createElement("input");
              newInput.type = "hidden";
              newInput.name = name;
              newInput.value = value;
              newInput.dataset.title = title;
              newInput.dataset.price = price;
              cartItemsContainer.appendChild(newInput);
            }

            // Add active class to group
            const group = checkbox.closest(".option-group");
            if (group) group.classList.add("active");
          } else {
            // ❌ IF UNCHECKED → value Empty
            if (existingInput) {
              existingInput.value = "";
              existingInput.dataset.price = "";
            }

            // Remove active class
            const group = checkbox.closest(".option-group");
            if (group) group.classList.remove("active");
          }

          // Recalculate pricing
          calculatePrice();
        });
      });

      // Select dropdowns
      const selectElement = step.querySelector("select");
      if (selectElement) {
        selectElement.addEventListener("change", (e) => {
          const name = e.target.name;
          const title = e.target.dataset.title || "";
          const value = e.target.value;
          // Get the selected <option>
          const selectedOption = e.target.selectedOptions[0];
          const price = selectedOption ? selectedOption.dataset.price || 0 : 0;

          // Try to find an existing input by name
          let existingInput = cartItemsContainer.querySelector(
            `input[name="${name}"]`
          );

          if (existingInput) {
            // ✅ Update the existing input
            existingInput.value = value;
            existingInput.dataset.title = title;
            existingInput.dataset.price = price;
            console.log(`Updated input: ${name} = ${value}`);
          } else {
            // ✅ Create a new hidden input if not found
            const newInput = document.createElement("input");
            newInput.type = "hidden";
            newInput.name = name;
            newInput.value = value;
            newInput.dataset.title = title;
            newInput.dataset.price = price;

            cartItemsContainer.appendChild(newInput);
            console.log(`Created new input: ${name} = ${value}`);
          }
          //calcuate
          calculatePrice();
        });
      }
    });

    // add to cart event
    const cartForm = document.querySelector("form.cart");
    if (!cartForm) return;

    cartForm.addEventListener("submit", function (e) {
      e.preventDefault();
      // calculate the price
      const { totalPrice, selections, item_key } = calculatePrice();
      console.log("selections", selections);

      //get quantity
      const quantityInput = cartForm.querySelector('input[name="quantity"]');
      const quantityValue = quantityInput ? quantityInput.value : 1;

      // get product ID
      const submitBtn = cartForm.querySelector('button[name="add-to-cart"]');
      const productId = submitBtn ? submitBtn.value : null;
      // Check if productId is null or an empty string
      if (!productId || productId.trim() === "") {
        alert("Something went wrong");
        return false;
      }

      // Prepare form data
      const formData = new FormData();
      formData.append("action", "wc_glass_product_add_to_cart");
      formData.append("product_id", productId);
      formData.append("total_price", totalPrice);
      formData.append("edit_key", item_key);
      formData.append("quantity", quantityValue);

      // Append the selections data, ensuring it's a JSON string
      formData.append("selections", JSON.stringify(selections));

      // Send AJAX request
      fetch(wc_add_to_cart_params.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((response) => {
          if (response.success) {
            // Redirect to cart page
            window.location.href = wc_add_to_cart_params.cart_url;
          } else {
            console.log("response", response);
          }
        })
        .catch(() => alert("Network error. Please try again."));
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  // Get all the radio button groups
  const radioWrappers = document.querySelectorAll(".radio-wrapper");

  radioWrappers.forEach((wrapper) => {
    wrapper.querySelectorAll('input[type="radio"]').forEach((radio) => {
      radio.addEventListener("change", (event) => {
        if (event.target.checked) {
          const parentLabel = event.target.nextElementSibling;
          const imageAltText =
            parentLabel.tagName === "LABEL"
              ? parentLabel.querySelector("img").alt
              : "";

          const parentStep = event.target.closest(".step");
          if (parentStep) {
            const h3Span = parentStep.querySelector("h3 span");
            if (h3Span) {
              h3Span.textContent = imageAltText;
            }
          }
        }
      });
    });
  });

  // Optional: Set initial value on page load
  radioWrappers.forEach((wrapper) => {
    const checkedRadio = wrapper.querySelector('input[type="radio"]:checked');
    if (checkedRadio) {
      const parentLabel = checkedRadio.nextElementSibling;
      const initialAltText = parentLabel
        ? parentLabel.querySelector("img").alt
        : "";
      const parentStep = checkedRadio.closest(".step");
      if (parentStep) {
        const h3Span = parentStep.querySelector("h3 span");
        if (h3Span) {
          h3Span.textContent = initialAltText;
        }
      }
    }
  });

  document.querySelectorAll(".option-item").forEach((element) => {
    element.addEventListener("click", function () {
      const item = this.closest(".option-item");
      const input = item.querySelector("input");

      if (!input) return;

      if (input.type === "checkbox") {
        // Toggle checkbox normally
        input.checked = !input.checked;

        item.classList.toggle("active", input.checked);
      }

      if (input.type === "radio") {
        // For radio buttons: uncheck all in group
        const groupName = input.name;
        const allInGroup = document.querySelectorAll(
          `.option-item input[name="${groupName}"]`
        );

        allInGroup.forEach((radio) => {
          radio.closest(".option-item").classList.remove("active");
        });

        // Check this radio
        input.checked = true;
        item.classList.add("active");
      }

      // Trigger change event
      input.dispatchEvent(new Event("change", { bubbles: true }));
    });
  });
});
