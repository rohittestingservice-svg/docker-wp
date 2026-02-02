document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form.cart');
    const priceElement = document.querySelector(".summary .woocommerce-Price-amount bdi");
    const basePriceInput = document.getElementById('base_product_price');
    const customPriceInput = document.querySelector('input[name="custom_price"]');
    const priceFields = document.querySelectorAll('.custm_field');

    
    function calculateTotalPrice() {
        let basePrice = parseFloat(basePriceInput?.value) || 0;
        let totalExtras = 0;

        priceFields.forEach(field => {
            const tag = field.tagName.toLowerCase();
        
            // For input elements (radio/checkbox/text)
            if (tag === 'input') {
                if ((field.type === 'radio' || field.type === 'checkbox') && field.checked) {
                    totalExtras += parseFloat(field.getAttribute('data-price')) || 0;
                } else if (field.type === 'text' || field.type === 'hidden') {
                    // Optional: if you have price in text fields
                    totalExtras += parseFloat(field.getAttribute('data-price')) || 0;
                }
            }
        
            // For select dropdowns
            if (tag === 'select') {
                const selectedOption = field.options[field.selectedIndex];
                if (selectedOption && selectedOption.hasAttribute('data-price')) {
                    totalExtras += parseFloat(selectedOption.getAttribute('data-price')) || 0;
                }
            }
        });

        console.log("basePrice", basePrice, "totalExtras", totalExtras)
        const finalPrice = basePrice + totalExtras;
        if (customPriceInput) {
            customPriceInput.value = finalPrice.toFixed(2);
            if (priceElement) {
                const priceText = priceElement.textContent;
                const updatedPrice = priceText.replace(/[\d.,]+/, finalPrice.toFixed(2)); // New price
                priceElement.textContent = updatedPrice;
            }
            
            // Find the price container
            const priceContainer = document.querySelector('.elementor-widget-container .price .amount bdi');
            if (priceContainer) {
                priceContainer.innerHTML = '<span class="woocommerce-Price-currencySymbol">$</span>' + finalPrice.toFixed(2);
            }
        }
    }

    // Recalculate on change of any relevant field
    priceFields.forEach(field => {
        field.addEventListener('change', calculateTotalPrice);
    });

    // Initial calculation in case some values are pre-filled
    calculateTotalPrice();
});
