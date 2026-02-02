jQuery(function ($) {
    let fieldCounter = 0;
  
    // Helper: create the required checkbox HTML
    function createRequiredCheckbox(inputId, isChecked = false) {
      return `
        <div class="required-checkbox-wrapper">
          <input type="checkbox" id="${inputId}" name="required" ${isChecked ? 'checked' : ''}>
          <label for="${inputId}">Required</label>
        </div>`;
    }
  
    // Helper: create delete button HTML
    function createDeleteButton() {
      return `<button class="delete-input-btn" title="Delete Input">×</button>`;
    }
  
    function createFieldContainer({ fieldName, inputId, type, heading = '', required = false, values = [] }, isVariant = false){
        console.log("createfield", isVariant)
        // Capitalize type for display
        const typeDisplay = type.charAt(0).toUpperCase() + type.slice(1);
  
        // Create options list if type is radio or select
        let optionsHtml = '';
        if (type === 'radio' || type === 'select') {
            if (values.length) {
                optionsHtml = values.map(opt => createOptionRow(opt, isVariant)).join('');
            } else {
                optionsHtml = createOptionRow({}, isVariant);
            }
        }
        console.log("type", type)
        return `
        <div class="input_container" data-field-type="${type}">
            <div class="input_heading">
                <input type="hidden" name="field_name" value="${fieldName}" data-field_type="${type}">
                <p class="input-type">Input Type: ${typeDisplay}</p>
                ${createRequiredCheckbox(inputId, required)}
                ${createDeleteButton()}
            </div>
            <div class="model-body">
                <input type="${type === 'textarea' ? 'textarea' : 'text'}" name="heading" class="heading-text" placeholder="Heading" value="${heading}">
                ${(type === 'textarea' || type === 'text') ? `<input type="number" name="price" placeholder="Price" value="23">` : '' }
                ${(type === 'radio' || type === 'select') ? `<div class="option_list">${optionsHtml}</div>
                <button type="button" class="custom-btn add-option-btn" style="margin-top: 5px;">Add Option</button>` : ''}
            </div>
        </div>`;
    }
    
    // Create option row for radio/select options
    function createOptionRow(option = {}, isVariant = false) {
        console.log("isVariant", isVariant)
        const isChecked = option.isDefault ? 'checked' : '';
        
        return `
            <div class="option-row">
                <input type="text" placeholder="Option heading" value="${option.heading || ''}">
                <input type="number" name="price" placeholder="Price" value="${option.price || ''}">
                ${isVariant ? `
                    <label style="margin-left: 8px;color: inherit;">
                        <input type="checkbox" name="default" ${isChecked}> Default
                    </label>` : ''
                }
                <button type="button" class="delete-btn">×</button>
            </div>`;
    }
    
    $(document).on('change', '.option-row input[type="checkbox"][name="default"]', function () {
        const main_model = $(this).closest('.main-model');
        main_model.find('input[type="checkbox"][name="default"]').not(this).prop('checked', false);
    });

    // Add input button click handler
    $('.add_input_btn').on('click', function () {
        const isVariant = ($(this).closest('.main-model').data('table-ref') === 'variant_table') ? true : false;
        const panel = $(this).closest('.woocommerce_options_panel');
        const type = panel.find('.input_type').val();
        if (!type) return;
  
        fieldCounter++;
        const timestamp = Date.now();
        const fieldName = 'field_' + timestamp + fieldCounter;
        const inputId = 'required_' + timestamp + fieldCounter;
  
        const fieldHtml = createFieldContainer({ fieldName, inputId, type, required: false, heading: '' }, isVariant);
        panel.find('.input_wrapper').append(fieldHtml);
    });
  
    // Add option button click handler (delegated)
    $(document).on('click', '.add-option-btn', function () {
        const isVariant = ($(this).closest('.main-model').data('table-ref') === 'variant_table') ? true : false;
        const optionList = $(this).siblings('.option_list');
        optionList.append(createOptionRow({}, isVariant));
    });
  
    // Delete option row button click handler (delegated)
    $(document).on('click', '.delete-btn', function () {
      $(this).closest('.option-row').remove();
    });
  
    // Delete input container button click handler (delegated)
    $(document).on('click', '.delete-input-btn', function (e) {
      e.preventDefault();
      const container = $(this).closest('.input_container');
      container.remove();
      //get main model
      const main_model = $(this).closest('.main-model');
      updateAndRenderData(main_model);
    });
  
    // Save button click handler
    $('.save_input_btn, .save_field_btn').on('click', function () {
        const main_model = $(this).closest('.main-model');
        updateAndRenderData(main_model);
        // Hide modal after saving
        main_model.hide();
    });
  
    // Edit button click handler
    $('.edit_btn, .edit_field_btn').on('click', function () {
      const panel = $(this).closest('.woocommerce_options_panel');
      const isVariant = (panel.find('.main-model').data('table-ref') === 'variant_table') ? true : false;
      console.log("isVariant field", isVariant)
      const inputWrapper = panel.find('.input_wrapper');
      inputWrapper.empty();
  
      let fieldsData = [];
      try {
        const dataInput = panel.find('#custom_variants_data, #custom_fields_data').first();
        if (dataInput.length && dataInput.val()) {
          fieldsData = JSON.parse(dataInput.val());
        }
      } catch {
        fieldsData = [];
      }
  
      fieldsData.forEach((field, index) => {
        const inputId = 'edit_' + Date.now() + '_' + index;
        const fieldHtml = createFieldContainer({
          fieldName: field.name || ('field_' + Date.now() + index),
          inputId,
          type: field.type || 'text',
          heading: field.heading || '',
          required: field.isRequired || false,
          values: field.values || []
        }, isVariant);

        inputWrapper.append(fieldHtml);
      });
  
      panel.find('.main-model').fadeIn();
    });
  
    // Close modal button
    $('.close_Btn').on('click', function () {
      $(this).closest('.main-model').hide();
    });
  
    // Add button to open modal
    $('.add_btn').on('click', function () {
      $(this).closest('.woocommerce_options_panel').find('.main-model').fadeIn();
    });
  
    // Render table from data array
    function renderDataTable(data, tableId = '') {
        if (!data || !Array.isArray(data) || !data.length) {
          $('#' + tableId).html('<p>No fields defined.</p>');
          return;
        }
      
        let html = `
          <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
              <tr>
                <th>Type</th>
                <th>Required</th>
                <th>Heading</th>
                <th>Values</th>
              </tr>
            </thead>
            <tbody>`;
      
        data.forEach(field => {
          html += `
            <tr>
              <td>${field.type}</td>
              <td>${field.isRequired ? 'Yes' : 'No'}</td>
              <td>${field.heading}</td>
              <td>`;
      
          if (field.values && field.values.length) {
            html += '<ul style="margin: 0; padding-left: 15px;">';
            field.values.forEach(opt => {
              html += `<li><strong>${opt.heading}</strong> (Price: €${opt.price})</li>`;
            });
            html += '</ul>';
          } else {
            html += '—';
          }
          html += '</td></tr>';
        });
      
        html += '</tbody></table>';
        $('#' + tableId).html(html);
      }
      
  
    // Update hidden inputs and render table
    function updateAndRenderData(main_model) {
        
      const allFields = [];
      
      main_model.find('.input_container').each(function () {
        const container = $(this);
        const name = container.find('input[name="field_name"]').val();
        const type = container.find('input[name="field_name"]').data('field_type');
        const required = container.find('input[name="required"]').is(':checked');
        const heading = container.find('input[name="heading"]').val() || '';
        
        const field = {
          name: name,
          type: type.toLowerCase(),
          isRequired: required,
          heading: heading
        };
  
        if (type === 'radio' || type === 'select') {
          const values = [];
          container.find('.option_list .option-row').each(function () {
            const optionRow = $(this);
            const optionHeading = optionRow.find('input[type="text"]').val() || '';
            const optionPrice = optionRow.find('input[name="price"]').val() || '';
            if (optionHeading || optionPrice) {
                const optionData = { heading: optionHeading, price: optionPrice };
    
                const defaultCheckbox = optionRow.find('input[name="default"]');
                if (defaultCheckbox.length && defaultCheckbox.is(':checked')) {
                  optionData.isDefault = true;
                }
            
                values.push(optionData);
            }
          });
          field.values = values;
        }
  
        allFields.push(field);
      });

      console.log("allFields", allFields)
  
      // Save data into hidden inputs if present
      main_model.find('.data_input').val(JSON.stringify(allFields));
      
      // Render table
      const table_id = main_model.data('table-ref');
      renderDataTable(allFields, table_id);
    }
  
    // Initial rendering if data exists on page load
    console.log("customVariantData", customVariantData)
    if (typeof customVariantData !== 'undefined' && customVariantData.variants) {
      renderDataTable(customVariantData.variants, 'variant_table');
    } else if ($('#custom_variants_data').val()) {
      try {
        renderDataTable(JSON.parse($('#custom_variants_data').val()), 'variant_table');
      } catch {}
    }

    // Initial rendering if data exists on page load
    console.log("customFieldsData", customFieldsData)
    if (typeof customFieldsData !== 'undefined' && customFieldsData.fields) {
        renderDataTable(customFieldsData.fields, 'fields_table' );
      } else if ($('#custom_fields_data').val()) {
        try {
          renderDataTable(JSON.parse($('#custom_variants_data').val()), 'fields_table');
        } catch {}
      }
  });
  