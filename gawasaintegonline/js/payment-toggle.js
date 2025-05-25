/**
 * Payment Method Toggle Script
 * This script controls the display of payment buttons based on the selected payment method
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get references to the payment method radio buttons
    const paymentOptions = document.querySelectorAll('input[name="payment"]');
    
    // Get references to the buttons
    const placeOrderBtn = document.querySelector('.place-order-btn');
    const directPaymentBtn = document.querySelector('.direct-payment-btn');
    
    // Apply styles to the direct payment button to match place order button
    if (directPaymentBtn) {
        // Copy styles from place order button
        directPaymentBtn.className = 'direct-payment-btn place-order-btn';
        directPaymentBtn.style.backgroundColor = '#E65100'; // Match primary orange color
        directPaymentBtn.style.color = 'white';
        directPaymentBtn.style.border = 'none';
        directPaymentBtn.style.borderRadius = '5px';
        directPaymentBtn.style.padding = '1rem';
        directPaymentBtn.style.width = '100%';
        directPaymentBtn.style.fontSize = '1.1rem';
        directPaymentBtn.style.fontWeight = 'bold';
        directPaymentBtn.style.cursor = 'pointer';
        directPaymentBtn.style.transition = 'background-color 0.3s';
    }
    
    // Function to toggle button visibility based on payment method
    function togglePaymentButtons() {
        // Get the currently selected payment method
        const selectedPayment = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
        
        console.log('Selected payment method:', selectedPayment);
        
        // Get display name for payment method
        let paymentMethodName = 'Cash on Delivery';
        if (selectedPayment === 'gcash') {
            paymentMethodName = 'GCash';
        } else if (selectedPayment === 'card') {
            paymentMethodName = 'Card';
        }
        
        // Update button text
        if (directPaymentBtn) {
            directPaymentBtn.textContent = `Place Order (${paymentMethodName})`;
        }
        
        if (selectedPayment === 'cod') {
            // For COD, show place order button and hide direct payment button
            if (placeOrderBtn) placeOrderBtn.style.display = 'block';
            if (directPaymentBtn) directPaymentBtn.style.display = 'none';
        } else {
            // For GCash/Card, hide place order button and show direct payment button
            if (placeOrderBtn) placeOrderBtn.style.display = 'none';
            if (directPaymentBtn) directPaymentBtn.style.display = 'block';
        }
    }
    
    // Add event listeners to payment method radio buttons
    paymentOptions.forEach(option => {
        option.addEventListener('change', togglePaymentButtons);
    });
    
    // Initial toggle on page load
    togglePaymentButtons();
    
    // Debug logging
    console.log('Payment toggle script initialized');
    console.log('Payment options found:', paymentOptions.length);
    console.log('Place order button found:', !!placeOrderBtn);
    console.log('Direct payment button found:', !!directPaymentBtn);
});
