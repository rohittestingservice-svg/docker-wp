document.addEventListener("DOMContentLoaded", function () {
  const builder = document.querySelector("#step-product-builder");
  if (!builder) return;

  const stepsContainer = builder.querySelector(".steps-container");

  // 🔁 Reindex input names properly
  function reindex() {
    const steps = stepsContainer.querySelectorAll(".step-block");
    steps.forEach((step, stepIndex) => {
      const titleInput = step.querySelector(
        'input[name^="steps["][name$="[title]"]'
      );
      if (titleInput) titleInput.name = `steps[${stepIndex}][title]`;

      const options = step.querySelectorAll(".option-block");
      options.forEach((option, optionIndex) => {
        const titleInput = option.querySelector('input[type="text"]');
        const priceInput = option.querySelector('input[type="number"]');
        if (titleInput)
          titleInput.name = `steps[${stepIndex}][options][${optionIndex}][title]`;
        if (priceInput)
          priceInput.name = `steps[${stepIndex}][options][${optionIndex}][price]`;
      });
    });
  }

  builder.addEventListener("click", function (e) {
    const target = e.target;

    // ➕ Add Step (clone first step or create fresh)
    if (target.classList.contains("add-step")) {
      let newStep;
      const firstStep = stepsContainer.querySelector(".step-block");

      if (firstStep) {
        // Clone existing structure
        newStep = firstStep.cloneNode(true);
        newStep.querySelectorAll("input").forEach((i) => (i.value = ""));
      } else {
        // Create new structure from scratch
        newStep = document.createElement("div");
        newStep.classList.add("step-block");
        newStep.innerHTML = `
          <div class="st-form-row">
            <div class="st-input-group">
              <input class="input" type="text" name="" placeholder="Enter step title">
            </div>
            <div class="st-actions">
              <a href="javascript:void(0)" class="remove-step">Remove</a>
            </div>
          </div>
          <div class="options-container">
            <div class="option-block">
              <input type="text" placeholder="Option title" />
              <input type="number" step="0.01" min="1" placeholder="Price" />
              <button type="button" class="button add-option">+</button>
              <button type="button" class="button remove-option">-</button>
            </div>
          </div>
          <hr>
        `;
      }

      stepsContainer.appendChild(newStep);
      reindex();
      return;
    }

    // ❌ Remove Step
    if (target.classList.contains("remove-step")) {
      const stepBlock = target.closest(".step-block");
      if (stepBlock) {
        stepBlock.remove();
        reindex();
      }
      return;
    }

    // ➕ Add Option (clone from current step’s first option)
    if (target.classList.contains("add-option")) {
      const stepBlock = target.closest(".step-block");
      const optionsContainer = stepBlock.querySelector(".options-container");
      const firstOption = optionsContainer.querySelector(".option-block");
      if (!firstOption) return;

      const newOption = firstOption.cloneNode(true);
      newOption
        .querySelectorAll("input")
        .forEach((input) => (input.value = ""));
      optionsContainer.appendChild(newOption);

      reindex();
      return;
    }

    // ❌ Remove Option (keep at least one)
    if (target.classList.contains("remove-option")) {
      const optionBlock = target.closest(".option-block");
      const optionsContainer = optionBlock?.parentNode;
      if (!optionBlock || !optionsContainer) return;

      optionBlock.remove();

      if (optionsContainer.querySelectorAll(".option-block").length === 0) {
        const newOption = document.createElement("div");
        newOption.classList.add("option-block");
        newOption.innerHTML = `
          <input type="text" placeholder="Option title" />
          <input type="number" step="0.01" min="1" placeholder="Price" />
          <button type="button" class="button add-option">+</button>
          <button type="button" class="button remove-option">-</button>
        `;
        optionsContainer.appendChild(newOption);
      }

      reindex();
      return;
    }
  });
});
