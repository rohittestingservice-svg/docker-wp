let fullConfig = []; 
let finalSteps = [];
let currentStepIndex = 0;
let selections = {};

const wrapper = document.getElementById("form-wrapper");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");

async function loadConfig() {
  const configUrl = `${configurator.baseUrl}/json/config.json`;
  const res = await fetch(configUrl);
  fullConfig = await res.json();
  console.log(fullConfig)
  finalSteps = [fullConfig[0]]; // Start with first step only
  renderStep(currentStepIndex);
}

function renderStep(index) {
  const step = finalSteps[index];
  if (!step) return;

  wrapper.innerHTML = ""; 

  const stepDiv = document.createElement("div");
  stepDiv.className = "step";

  // Heading with required indicator
  const headingWrapper = document.createElement("div");
  headingWrapper.className = "heading-wrapper";
  headingWrapper.innerHTML = `<h3>${step.heading}</h3>`;
  if (step.required) {
    headingWrapper.innerHTML += `<span class="validate"> Verplicht</span>`;
  }
  stepDiv.appendChild(headingWrapper);

  // RADIO input type
  if (step.type === "radio") {
    step.data.forEach((option) => {
      const group = document.createElement("div");
      group.className = "radio-group";

      const input = document.createElement("input");
      input.type = "radio";
      input.name = step.heading;
      input.value = option.label;
      if (selections[step.heading] === option.label) input.checked = true;

      const label = document.createElement("label");
      label.innerText = option.label;

      // Clicking the whole group selects the radio
      group.addEventListener("click", () => {
        input.checked = true;
        selections[step.heading] = option.label;
        //preview img
        if (option.img_src && option.img_src.trim() !== '') {
          const img = document.getElementById('selected_img');
          img.src = `${configurator.baseUrl}/images/${option.img_src}`;
        }
          
      });

      group.appendChild(input);
      group.appendChild(label);

      //img
      if (option.img_src && option.img_src.trim() !== '') {
        const img = document.createElement('img');
        img.src = `${configurator.baseUrl}/images/${option.img_src}`;
        img.width= 100;
        img.height = 30;
        group.appendChild(img);
      }
      
      stepDiv.appendChild(group);
    });
  }

  // RANGE input type
  else if (step.type === "range") {
    for (const [label, range] of Object.entries(step.data)) {
      const group = document.createElement("div");
      group.className = "input-wrapper";

      // Label wrapper
      const labelGroup = document.createElement("div");
      labelGroup.className = "label-wrapper";

      const labelEl = document.createElement("label");
      labelEl.innerText = label;

      labelGroup.appendChild(labelEl);

      // Validation message span (above input)
      const validationMsg = document.createElement("span");
      validationMsg.className = "err_msg";

      // Range group
      const rangeGroup = document.createElement("div");
      rangeGroup.className = "range-group";

      const key = `${step.heading} - ${label}`;
      const savedValue = selections[key] ?? range.min;

      const numberInput = document.createElement("input");
      numberInput.type = "number";
      numberInput.value = savedValue;

      const minSpan = document.createElement("span");
      minSpan.innerText = range.min;

      const rangeInput = document.createElement("input");
      rangeInput.type = "range";
      rangeInput.min = range.min;
      rangeInput.max = range.max;
      rangeInput.value = savedValue;

      const maxSpan = document.createElement("span");
      maxSpan.innerText = range.max;

      // Validation logic
      const validateValue = (val) => {
        if (val <= parseInt(range.min)) {
          validationMsg.innerText = `Waarde moet groter zijn dan ${range.min}`;
          delete selections[key];
        } else if (val >= parseInt(range.max)) {
          validationMsg.innerText = `Waarde moet kleiner zijn dan ${range.max}`;
          delete selections[key];
        } else {
          validationMsg.innerText = "";
          selections[key] = val;
        }
      };

      // Sync range -> number
      rangeInput.addEventListener("input", () => {
        const val = parseInt(rangeInput.value);
        numberInput.value = val;
        validateValue(val);
      });

      // Sync number -> range
      numberInput.addEventListener("input", () => {
        let val = parseInt(numberInput.value);
        const min = parseInt(rangeInput.min);
        const max = parseInt(rangeInput.max);

        if (isNaN(val)) return;
        if (val < min) val = min;
        if (val > max) val = max;

        numberInput.value = val;
        rangeInput.value = val;
        validateValue(val);
      });

      group.appendChild(labelGroup);
      rangeGroup.appendChild(numberInput);
      rangeGroup.appendChild(minSpan);
      rangeGroup.appendChild(rangeInput);
      rangeGroup.appendChild(maxSpan);
      group.appendChild(rangeGroup);
      group.appendChild(validationMsg);

      stepDiv.appendChild(group);
    }
  } else if (step.type === "multi-input") {
    const group = document.createElement("div");
    group.className = "multi-input-group";

    step.data.forEach((field) => {
      const wrapper = document.createElement("div");
      wrapper.className = "input-wrapper";

      // Label
      const labelEl = document.createElement("label");
      labelEl.innerHTML = field.label + (field.required ? ' <span style="color: red">*</span>' : '');
      labelEl.setAttribute("for", field.name);

      // Input or textarea
      let inputEl;
      if (field.type === "textarea") {
        inputEl = document.createElement("textarea");
      } else {
        inputEl = document.createElement("input");
        inputEl.type = field.type;
      }

      inputEl.id = field.name;
      inputEl.name = field.name;
      inputEl.required = field.required || false;
      inputEl.className = "input-field";

      // Error span for validation
      const errorSpan = document.createElement("span");
      errorSpan.className = "err_msg";
      errorSpan.style.display = "none";
      errorSpan.innerText = `${field.label} is verplicht`;

      // Event listener to store data
      inputEl.addEventListener("input", () => {
        selections[field.name] = inputEl.value.trim();
        if (field.required && !inputEl.value.trim()) {
          inputEl.style.borderColor = "red";
          errorSpan.style.display = "block";
        } else {
          inputEl.style.borderColor = "#d1d5db";
          errorSpan.style.display = "none";
        }
      });

      wrapper.appendChild(labelEl);
      wrapper.appendChild(inputEl);
      wrapper.appendChild(errorSpan);
      group.appendChild(wrapper);
    });

    stepDiv.appendChild(group);
  }

  // Add stepDiv to wrapper
  wrapper.appendChild(stepDiv);

  // Button logic
  prevBtn.style.display = index > 0 ? "inline-block" : "none";

  // Handle final step
  if (step.final) {
    nextBtn.innerText = "Bevestigen";
  } else {
    nextBtn.innerText = "Volgende";
  }
}

// Go to next step with sub_data logic
async function goToNextStep() {
  const currentStep = finalSteps[currentStepIndex];
  let isValid = true;

  // Validation: check for required fields
  if (currentStep.required) {
    if (currentStep.type === "range") {
      // Check if at least one value is selected for all range entries
      const keys = Object.keys(currentStep.data || {});
      for (const key of keys) {
        if (!selections[`${currentStep.heading} - ${key}`]) {
          isValid = false;
          break;
        }
      }
    } else if (currentStep.type === "multi-input") {
      const inputFields = currentStep.data || [];
      for (const field of inputFields) {
        if (!selections[`${field.name}`]) {
          isValid = false;
          break;
        }
      }
    } else {
      if (!selections[currentStep.heading]) {
        isValid = false;
      }
    }
  }

  // Highlight if invalid
  if (!isValid) {
    const headingWrappers = document.querySelectorAll(".heading-wrapper");
    headingWrappers.forEach((wrapper) => {
      const h3 = wrapper.querySelector("h3");
      const span = wrapper.querySelector(".validate");
      if (h3 && h3.innerText.trim() === currentStep.heading && span) {
        span.classList.add("highlight-required");
      }
    });
    return;
  }

  // Remove highlight if valid
  const allValidateSpans = document.querySelectorAll(".validate");
  allValidateSpans.forEach((span) =>
    span.classList.remove("highlight-required")
  );

  const selectedLabel = selections[currentStep.heading];
  let selectedOption = null;

  if (currentStep.type === "radio" && Array.isArray(currentStep.data)) {
    selectedOption = currentStep.data.find(
      (opt) => opt.label === selectedLabel
    );
  }

  // Final step check
  if (currentStep.final) {
    console.log("Form completed!");
    console.log("Selections:", selections);
    ajaxSubmit(selections)
    return;
  }

  // Inject sub_data if available
  if (selectedOption?.sub_data) {
    const subSteps = selectedOption.sub_data.map((step) => ({
      ...step,
      fromSubData: true,
    }));
    finalSteps.splice(currentStepIndex + 1, 0, ...subSteps);
  }

  if (selectedOption?.sub_data_file){
    const response = await fetch(`${configurator.baseUrl}/json/${selectedOption.sub_data_file}`);
    const externalSubData = await response.json();
    finalSteps.splice(currentStepIndex + 1, 0, ...externalSubData);
  }

  // Move to next step
  if (currentStepIndex < finalSteps.length - 1) {
    currentStepIndex++;
    renderStep(currentStepIndex);
  }

}

function goToPreviousStep() {
  if (currentStepIndex <= 0) return;

  const currentStep = finalSteps[currentStepIndex];
  
  // Remove selections of current step
  if (currentStep.type === "range") {
    for (const label of Object.keys(currentStep.data || {})) {
      delete selections[`${currentStep.heading} - ${label}`];
    }
  } else {
    if(selections.hasOwnProperty(currentStep.heading)){
      delete selections[currentStep.heading];
    }else{
      console.log("currentStep", currentStep)
    }
  }

  // Remove sub_data steps (if any were dynamically added)
  const nextStep = finalSteps[currentStepIndex];
  
  if (nextStep?.fromSubData) {
    // Remove all sub_data steps that were added dynamically
    while (finalSteps[currentStepIndex]?.fromSubData) {
      finalSteps.splice(currentStepIndex, 1);
    }
  }

  currentStepIndex--;``
  renderStep(currentStepIndex);
}

// Reusable AJAX function
function ajaxSubmit(formElement) {
  const formData = new FormData();
  // Add extra data
  for (const key in formElement) {
    formData.append(key, formElement[key]);
  }

  // Add WordPress-specific required fields
  formData.append('action', 'configurator_form_submission'); 
  
  jQuery.ajax({
    url: configurator.ajax_url, 
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      console.log("response", response)
      if (response.success) {
        //redirect into thankyou page
        window.location.href = '/thank-you'; 
      } else {
        alert(response.data.message);
      }
    },
    error: function(err) {
      console.error('AJAX request error:', err);
    }
  });
}

// Event listeners
nextBtn.addEventListener("click", goToNextStep);
prevBtn.addEventListener("click", goToPreviousStep);

// Load everything
loadConfig();
