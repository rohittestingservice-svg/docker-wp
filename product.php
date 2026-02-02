<?php
$json = '[
  {
    "type": "input",
    "required": true,
    "heading": "Vel maiores sit pos",
    "options": []
  },
  {
    "type": "paragraph",
    "required": true,
    "heading": "",
    "options": []
  },
  {
    "type": "radio",
    "required": true,
    "heading": "Quos officia aut eos",
    "options": [
      {
        "label": "Voluptatum ut ullamc",
        "stock": "95",
        "price": "203"
      },
      {
        "label": "Facilis qui tempore",
        "stock": "66",
        "price": "31"
      },
      {
        "label": "Qui voluptates labor",
        "stock": "27",
        "price": "428"
      }
    ]
  },
  {
    "type": "select",
    "required": false,
    "heading": "Minima irure distinc",
    "options": [
      {
        "label": "Officia enim odit cu",
        "stock": "28",
        "price": "123"
      },
      {
        "label": "Natus voluptatibus e",
        "stock": "13",
        "price": "757"
      },
      {
        "label": "Incidunt molestiae ",
        "stock": "16",
        "price": "602"
      }
    ]
  }
]';

$data = json_decode($json, true);

foreach ($data as $index => $input) {
    echo '<div class="input_container">';
    $isRequired = !empty($input['required']) ? 'required' : '';

    switch ($input['type']) {
        case 'input':
            echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
            echo '<input type="text" name="input_' . $index . '" ' . $isRequired . '><br>';
            break;

        case 'paragraph':
            echo '<p>This is a paragraph block.</p>';
            break;

        case 'radio':
            echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
            foreach ($input['options'] as $optIndex => $option) {
                echo '<div class="option-row">';
                echo '<input type="radio" name="radio_' . $index . '" value="' . htmlspecialchars($option['label']) . '" ' . $isRequired . '>';
                echo '<label>' . htmlspecialchars($option['label']);
                if (!empty($option['price'])) {
                    echo ' (+' . htmlspecialchars($option['price']) . ')';
                }
                echo '</label>';
                echo '</div>';
            }
            break;

        case 'select':
            echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
            echo '<select name="select_' . $index . '" ' . $isRequired . '>';
            echo '<option value="">Make a choice..</option>';
            foreach ($input['options'] as $option) {
                echo '<option value="' . htmlspecialchars($option['label']) . '">';
                echo htmlspecialchars($option['label']);
                if (!empty($option['price'])) {
                    echo ' (+' . htmlspecialchars($option['price']) . ')';
                }
                echo '</option>';
            }
            echo '</select><br>';
            break;

        default:
            echo '<p>Unsupported input type: ' . htmlspecialchars($input['type']) . '</p>';
            break;
    }

    echo '</div><br>';
}
?>
