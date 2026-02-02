<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Multiple Editable Dynamic Inputs</title>
  <style>
    .input_container {
      border: 1px solid #ccc;
      padding: 10px;
      margin-bottom: 15px;
    }
    .option-row {
      margin-bottom: 5px;
      display: flex;
      align-items: center;
    }
    input[type="text"] {
      margin-right: 10px;
    }
    .delete-btn {
      margin-left: 10px;
      cursor: pointer;
      background-color: red;
      color: white;
      padding: 3px 8px;
      border: none;
      border-radius: 4px;
    }
  </style>
</head>
<body>

<form action="">
  <label for="input_type">Choose Input Type:</label>
  <select id="input_type">
    <option value="">Make choice..</option>
    <option value="input">Input</option>
    <option value="paragraph">Paragraph</option>
    <option value="radio">Radio</option>
    <option value="select">Select</option>
  </select>

  <button type="button" id="add_Btn">Add</button>
  <button type="button" id="save_Btn">Save</button>
  
  <div id="input_wrapper" style="margin-top: 15px;"></div>    
</form>

<pre id="output" style="margin-top: 20px; background: #f9f9f9; padding: 10px;"></pre>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('add_Btn');
    const saveBtn = document.getElementById('save_Btn');
    const inputType = document.getElementById('input_type');
    const inputWrapper = document.getElementById("input_wrapper");
    const output = document.getElementById("output");

    addBtn.addEventListener('click', function () {
        const selected = inputType.value;

        if (!selected) {
            alert('Please select an input type');
            return;
        }

        const container = document.createElement('div');
        container.className = 'input_container';

        // Create Delete Input button
        const deleteInputBtn = document.createElement('button');
        deleteInputBtn.type = 'button';
        deleteInputBtn.textContent = 'Delete Input';
        deleteInputBtn.className = 'delete-btn';
        deleteInputBtn.style.marginLeft = '10px';
        deleteInputBtn.style.marginTop = '10px';
        //remove event
        deleteInputBtn.addEventListener('click', () => {
            container.remove();
        });
        container.appendChild(deleteInputBtn);

        //heading
        const heading = document.createElement('p');
        heading.textContent = `Input Type: ${selected}`;
        container.appendChild(heading);

        //required input
        const requiredLabel = document.createElement('label');
        requiredLabel.textContent = "Required:";

        // Yes radio
        const requiredYes = document.createElement('input');
        requiredYes.type = 'radio';
        requiredYes.name = `required_${Date.now()}`;
        requiredYes.value = 'yes';
        requiredYes.checked = true;

        // No radio
        const requiredNo = document.createElement('input');
        requiredNo.type = 'radio';
        requiredNo.name = requiredYes.name; // Same group
        requiredNo.value = 'no';

        container.appendChild(requiredLabel);
        container.appendChild(requiredYes);
        container.appendChild(document.createTextNode("Yes"));
        container.appendChild(requiredNo);
        container.appendChild(document.createTextNode("No"));

        //check for input type
        if(selected === 'paragraph'){
            const headingInput = document.createElement('textarea');
            headingInput.name = 'text-content';
            headingInput.placeholder = 'Add text';
            container.appendChild(headingInput);

        }else{

            const headingInput = document.createElement('input');
            headingInput.type = 'text';
            headingInput.name = 'heading';
            headingInput.placeholder = 'Heading';
            container.appendChild(headingInput);
        
            if(selected == 'select' || selected == 'radio'){
                const optionList = document.createElement('div');
                optionList.className = 'option_list';

                const addOptionBtn = document.createElement('button');
                addOptionBtn.type = 'button';
                addOptionBtn.textContent = 'Add Option';
                addOptionBtn.style.marginTop = '5px';

                function addOptionField() {
                    const optionRow = document.createElement('div');
                    optionRow.className = 'option-row';

                    const labelInput = document.createElement('input');
                    labelInput.type = 'text';
                    labelInput.placeholder = 'Option label';
                    optionRow.appendChild(labelInput);

                    const stockInput = document.createElement('input');
                    stockInput.type = 'number';
                    stockInput.name = 'total_stock';
                    stockInput.placeholder = 'Stock';
                    optionRow.appendChild(stockInput);

                    const priceInput = document.createElement('input');
                    priceInput.type = 'number';
                    priceInput.name = 'price';
                    priceInput.placeholder = 'Price';
                    optionRow.appendChild(priceInput);

                    // Add delete button
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.textContent = 'Delete';
                        deleteBtn.addEventListener('click', function () {
                        optionRow.remove();
                        });

                    optionRow.appendChild(deleteBtn);
                    optionList.appendChild(optionRow);
                }

                // Add initial 1 option fields
                addOptionField();

                addOptionBtn.addEventListener('click', addOptionField);

                container.appendChild(optionList);
                container.appendChild(addOptionBtn);
            }
        }
        inputWrapper.appendChild(container);
    });

    saveBtn.addEventListener('click', function () {
      const containers = document.querySelectorAll('.input_container');
      const data = [];

        containers.forEach(container => {
            const options = [];
            const typeText = container.querySelector('p').textContent.replace('Input Type: ', '');
            //heading
            const headingInput = container.querySelector('input[name="heading"]');
            const heading = headingInput ? headingInput.value.trim() : '';
            //required
            const requiredInput = container.querySelector('input[name^="required"]:checked');
            const required = requiredInput ? requiredInput.value === 'yes' : false;

            //condition, heading is required
            if(heading.length !== 0){
                const optionRows = container.querySelectorAll('.option-row');
                optionRows.forEach(row => {
                    const inputs = row.querySelectorAll('input');
                    console.log("inputs", inputs)
                    const option = {
                        label: inputs.length >= 1 ? inputs[0].value : '', // Option label
                        stock: inputs.length >= 2 ? inputs[1].value : '', // Stock
                        price: inputs.length >= 3 ? inputs[2].value : ''  // Price
                    };

                    options.push(option);
            
                });
            }
            data.push({
                type: typeText,
                required: required,
                heading: heading,
                options: options ?? []
            });
      });

      const jsonOutput = JSON.stringify(data, null, 2);
      output.textContent = jsonOutput;
      console.log(jsonOutput); // optionally send via AJAX here
    });
  });
</script>

</body>
</html>
