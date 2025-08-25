// Admin Dashboard JavaScript
// Global variables
let cart = [];
let selectedPetType = '';

// Modal Management
function openModal(modalId, type = '') {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        
        // Special handling for appointment modal
        if (modalId === 'appointmentModal') {
            const title = document.getElementById('appointmentModalTitle');
            const form = document.getElementById('appointmentForm');
            
            if (title && form) {
                if (type === 'emergency') {
                    title.textContent = 'Emergency Appointment';
                    const statusSelect = form.querySelector('[name="status"]');
                    const dateInput = form.querySelector('[name="appointment_date"]');
                    if (statusSelect) statusSelect.value = 'confirmed';
                    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
                } else {
                    title.textContent = 'Schedule Appointment';
                    const statusSelect = form.querySelector('[name="status"]');
                    if (statusSelect) statusSelect.value = 'pending';
                }
            }
        }
    } else {
        console.error('Modal not found:', modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        // Reset form if exists
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// Form Submission
function submitForm(form, action, successMessage) {
    const formData = new FormData(form);
    formData.append('action', action);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(successMessage);
            form.reset();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Search function
function searchTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }

        row.style.display = found ? '' : 'none';
    }
}

// Filter appointments
function filterAppointments(filter) {
    const table = document.getElementById('appointmentTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    const today = new Date().toISOString().split('T')[0];
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const status = row.dataset.status;
        const date = row.dataset.date;
        let show = true;

        switch (filter) {
            case 'all':
                show = true;
                break;
            case 'today':
                show = date === today;
                break;
            case 'tomorrow':
                show = date === tomorrowStr;
                break;
            case 'pending':
            case 'confirmed':
            case 'completed':
                show = status === filter;
                break;
        }

        row.style.display = show ? '' : 'none';
    }
}

// Update appointment status
function updateStatus(appointmentId, status, statusElement) {
    const formData = new FormData();
    formData.append('action', 'update_appointment_status');
    formData.append('appointment_id', appointmentId);
    formData.append('status', status);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusElement.className = `status status-${status}`;
            statusElement.closest('tr').dataset.status = status;
        } else {
            alert('Error updating status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating status.');
    });
}

// POS System Functions
function addToCart(productId, productName, price, stock, type = 'product') {
    const existingItem = cart.find(item => item.id === productId && item.type === type);

    if (existingItem) {
        if (type === 'product' && existingItem.quantity < stock) {
            existingItem.quantity++;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else if (type === 'service') {
            existingItem.quantity++;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else {
            alert('Cannot add more items. Stock limit reached.');
            return;
        }
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: price,
            quantity: 1,
            total: price,
            stock: stock,
            type: type
        });
    }
    updateCartDisplay();
}

function switchTab(tabName) {
    document.querySelectorAll('.pos-section').forEach(section => {
        section.style.display = 'none';
    });
    
    document.querySelectorAll('#productsTab, #servicesTab').forEach(tab => {
        tab.classList.remove('active');
        tab.style.background = '#64748b';
    });

    if (tabName === 'products') {
        const productsSection = document.getElementById('productsSection');
        const productsTab = document.getElementById('productsTab');
        if (productsSection && productsTab) {
            productsSection.style.display = 'block';
            productsTab.classList.add('active');
            productsTab.style.background = '#3b82f6';
        }
    } else if (tabName === 'services') {
        const servicesSection = document.getElementById('servicesSection');
        const servicesTab = document.getElementById('servicesTab');
        if (servicesSection && servicesTab) {
            servicesSection.style.display = 'block';
            servicesTab.classList.add('active');
            servicesTab.style.background = '#3b82f6';
        }
    }
}

function removeFromCart(productId, type) {
    cart = cart.filter(item => !(item.id === productId && item.type === type));
    updateCartDisplay();
}

function updateQuantity(productId, newQuantity, type) {
    const item = cart.find(item => item.id === productId && item.type === type);
    if (item) {
        if (type === 'service' || (newQuantity <= item.stock && newQuantity > 0)) {
            item.quantity = newQuantity;
            item.total = item.quantity * item.price;
            updateCartDisplay();
        } else if (newQuantity <= 0) {
            removeFromCart(productId, type);
        } else {
            alert('Quantity exceeds available stock.');
        }
    }
}

function updateCartDisplay() {
    const cartContainer = document.getElementById('cartItems');
    if (!cartContainer) return;

    const cartSubtotal = cart.reduce((sum, item) => sum + item.total, 0);
    const cartTotal = cartSubtotal;
    
    const subtotalElement = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    
    if (subtotalElement) subtotalElement.textContent = '₱' + cartSubtotal.toFixed(2);
    if (totalElement) totalElement.textContent = '₱' + cartTotal.toFixed(2);

    if (cart.length === 0) {
        cartContainer.innerHTML = '<p style="color: #94a3b8; text-align: center; margin-top: 2rem;">No items in cart</p>';
        return;
    }

    let cartHTML = '';
    cart.forEach(item => {
        cartHTML += `
            <div style="background: #475569; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: white; font-weight: 500;">${item.name}</span>
                        <span style="background: ${item.type === 'service' ? '#10b981' : '#3b82f6'}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem; text-transform: uppercase;">${item.type}</span>
                    </div>
                    <button class="remove-cart-item" data-id="${item.id}" data-type="${item.type}" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 2px 6px; cursor: pointer; font-size: 0.7rem;">×</button>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; color: #cbd5e1; font-size: 0.8rem;">
                    <span>₱${item.price.toFixed(2)} × ${item.quantity}</span>
                    <span style="font-weight: 500;">₱${item.total.toFixed(2)}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <button class="decrease-quantity" data-id="${item.id}" data-type="${item.type}" style="background: #64748b; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer;">-</button>
                    <span style="color: white; font-size: 0.8rem; min-width: 20px; text-align: center;">${item.quantity}</span>
                    <button class="increase-quantity" data-id="${item.id}" data-type="${item.type}" style="background: #64748b; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer;">+</button>
                </div>
            </div>
        `;
    });
    cartContainer.innerHTML = cartHTML;
}

function processSale() {
    if (cart.length === 0) {
        alert('Please add items to cart before processing sale.');
        return;
    }
    if (!selectedPetType) {
        alert('Please select a pet type before processing sale.');
        return;
    }

    const paymentMethod = prompt('Select payment method:\n1. Cash\n2. Card\n3. GCash\n4. PayMaya\n5. Bank Transfer\n\nEnter number (1-5):');
    const paymentMethods = ['Cash', 'Card', 'GCash', 'PayMaya', 'Bank Transfer'];
    const selectedMethod = paymentMethods[parseInt(paymentMethod) - 1] || 'Cash';

    const cartTotal = cart.reduce((sum, item) => sum + item.total, 0);
    const saleData = new FormData();
    saleData.append('action', 'record_sale');
    saleData.append('items', JSON.stringify(cart.map(item => ({ 
        name: item.name, 
        quantity: item.quantity, 
        price: item.price, 
        total: item.total, 
        type: item.type 
    }))));
    saleData.append('total_amount', cartTotal);
    saleData.append('payment_method', selectedMethod);
    saleData.append('pet_type', selectedPetType);

    fetch('', {
        method: 'POST',
        body: saleData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sale processed successfully! Total: ₱' + cartTotal.toFixed(2));
            cart = [];
            selectedPetType = '';
            const petTypeSelect = document.getElementById('petTypeSelect');
            const selectedPetTypeSpan = document.getElementById('selectedPetType');
            if (petTypeSelect) petTypeSelect.value = '';
            if (selectedPetTypeSpan) selectedPetTypeSpan.textContent = '';
            updateCartDisplay();
            location.reload();
        } else {
            alert('Error processing sale: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the sale.');
    });
}

function printReceipt(saleId) {
    const saleRow = document.querySelector(`tr[data-sale-id="${saleId}"]`);
    if (!saleRow) {
        alert('Sale data not found. Please refresh the page and try again.');
        return;
    }

    try {
        const sale = {
            id: saleId,
            sale_date: saleRow.querySelector('td:nth-child(2)').textContent.trim(),
            pet_type: saleRow.querySelector('td:nth-child(3)').textContent.trim(),
            products: saleRow.querySelector('td:nth-child(4)').textContent.trim(),
            services: saleRow.querySelector('td:nth-child(5)').textContent.trim(),
            total_amount: saleRow.querySelector('td:nth-child(6)').textContent.replace('₱', '').replace(/,/g, '').trim(),
            payment_method: saleRow.querySelector('td:nth-child(7)').textContent.trim()
        };

        const printWindow = window.open('', '_blank', 'width=800,height=600');
        if (!printWindow) {
            alert('Please allow popups for this site to print receipts.');
            return;
        }

        const receiptContent = generateReceiptHTML(sale);
        printWindow.document.write(receiptContent);
        printWindow.document.close();
        printWindow.focus();

        setTimeout(() => {
            try {
                printWindow.print();
            } catch (printError) {
                console.error('Print error:', printError);
                alert('Print failed. Please try printing manually from the new window.');
            }
        }, 1000);
    } catch (error) {
        console.error('Error in printReceipt:', error);
        alert('Error generating receipt. Please try again.');
    }
}

function generateReceiptHTML(sale) {
    const dateParts = sale.sale_date.split(' ');
    const formattedDate = dateParts.slice(0, 3).join(' ');
    const formattedTime = dateParts[3] || '12:00';
    let itemsHTML = '';
    let petType = sale.pet_type || 'N/A';

    if (sale.products && sale.products !== 'None') {
        itemsHTML += `<div class="section-title">Products:</div>`;
        const products = sale.products.split(', ');
        products.forEach(item => {
            itemsHTML += `
                <div class="item">
                    <span>${item}</span>
                    <span>₱0.00</span>
                </div>
            `;
        });
    }

    if (sale.services && sale.services !== 'None') {
        itemsHTML += `<div class="section-title">Services:</div>`;
        const services = sale.services.split(', ');
        services.forEach(item => {
            itemsHTML += `
                <div class="item">
                    <span>${item}</span>
                    <span>₱0.00</span>
                </div>
            `;
        });
    }

    if (!itemsHTML) {
        itemsHTML = `
            <div class="item">
                <span>Sale Items</span>
                <span>₱0.00</span>
            </div>
        `;
    }

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>MavetCare Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .receipt { max-width: 400px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                .logo { font-size: 24px; font-weight: bold; color: #8BC34A; }
                .sale-info { margin-bottom: 20px; }
                .items { margin-bottom: 20px; }
                .item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                .section-title { font-weight: bold; margin: 10px 0 5px 0; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
                .total { border-top: 1px solid #333; padding-top: 10px; font-weight: bold; font-size: 18px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                @media print { body { margin: 0; } .receipt { max-width: none; } }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <div class="logo">MavetCare</div>
                    <div>Veterinary Clinic</div>
                    <div>Receipt #${sale.id}</div>
                </div>
                <div class="sale-info">
                    <div>Date: ${formattedDate}</div>
                    <div>Time: ${formattedTime}</div>
                    <div>Pet Type: ${petType}</div>
                    <div>Payment: ${sale.payment_method}</div>
                </div>
                <div class="items">
                    ${itemsHTML}
                </div>
                <div class="total">
                    <div class="item">
                        <span>Total:</span>
                        <span>₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                    </div>
                </div>
                <div class="footer">
                    Thank you for choosing MavetCare!<br>
                    Leave your pets in safe hands.
                </div>
            </div>
        </body>
        </html>
    `;
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Dashboard initialized');

    // Set minimum date for appointment forms
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (input.name === 'appointment_date') {
            input.min = today;
        }
    });

    // Initialize pet type selection
    const petTypeSelect = document.getElementById('petTypeSelect');
    if (petTypeSelect) {
        petTypeSelect.addEventListener('change', function() {
            selectedPetType = this.value;
            const selectedPetTypeSpan = document.getElementById('selectedPetType');
            if (selectedPetTypeSpan) {
                if (selectedPetType) {
                    selectedPetTypeSpan.textContent = 'Selected: ' + selectedPetType;
                    selectedPetTypeSpan.style.color = '#10b981';
                } else {
                    selectedPetTypeSpan.textContent = '';
                }
            }
        });
    }

    // Modal open buttons
    const modalButtons = {
        'addPatientBtn': 'patientModal',
        'addDoctorBtn': 'doctorModal',
        'addServiceBtn': 'serviceModal',
        'addStaffBtn': 'staffModal',
        'addInventoryBtn': 'inventoryModal',
        'emergencyAppointmentBtn': 'appointmentModal',
        'regularAppointmentBtn': 'appointmentModal'
    };

    Object.keys(modalButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                const modalId = modalButtons[buttonId];
                const type = buttonId.includes('emergency') ? 'emergency' : 'regular';
                openModal(modalId, type);
            });
        }
    });

    // Modal close buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('close')) {
            const modalId = e.target.dataset.modal;
            if (modalId) {
                closeModal(modalId);
            }
        }
    });

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // Form submissions
    const forms = {
        'patientForm': ['add_patient', 'Patient added successfully!'],
        'doctorForm': ['add_staff', 'Doctor added successfully!'],
        'appointmentForm': ['add_appointment', 'Appointment scheduled successfully!'],
        'serviceForm': ['add_service', 'Service added successfully!'],
        'staffForm': ['add_staff', 'Staff member added successfully!'],
        'inventoryForm': ['add_inventory', 'Item added to inventory successfully!'],
        'salesForm': ['record_sale', 'Sale recorded successfully!']
    };

    Object.keys(forms).forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const [action, message] = forms[formId];
                submitForm(this, action, message);
            });
        }
    });

    // POS Product/Service click handlers
    document.addEventListener('click', function(e) {
        const productItem = e.target.closest('.pos-product-item');
        if (productItem) {
            const id = productItem.dataset.id;
            const name = productItem.dataset.name;
            const price = parseFloat(productItem.dataset.price);
            const stock = parseInt(productItem.dataset.stock);
            const type = productItem.dataset.type;
            addToCart(id, name, price, stock, type);
        }
    });

    // Tab switching
    const productsTab = document.getElementById('productsTab');
    const servicesTab = document.getElementById('servicesTab');
    
    if (productsTab) {
        productsTab.addEventListener('click', function() {
            switchTab('products');
        });
    }
    
    if (servicesTab) {
        servicesTab.addEventListener('click', function() {
            switchTab('services');
        });
    }

    // Process sale button
    const processSaleBtn = document.getElementById('processSaleBtn');
    if (processSaleBtn) {
        processSaleBtn.addEventListener('click', processSale);
    }

    // Cart button event listeners
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-cart-item')) {
            const id = e.target.dataset.id;
            const type = e.target.dataset.type;
            removeFromCart(id, type);
        }

        if (e.target.classList.contains('decrease-quantity')) {
            const id = e.target.dataset.id;
            const type = e.target.dataset.type;
            const item = cart.find(item => item.id == id && item.type === type);
            if (item) {
                updateQuantity(id, item.quantity - 1, type);
            }
        }

        if (e.target.classList.contains('increase-quantity')) {
            const id = e.target.dataset.id;
            const type = e.target.dataset.type;
            const item = cart.find(item => item.id == id && item.type === type);
            if (item) {
                updateQuantity(id, item.quantity + 1, type);
            }
        }
    });

    // Action button event listeners
    document.addEventListener('click', function(e) {
        const action = e.target.dataset.action || e.target.closest('[data-action]')?.dataset.action;
        if (action) {
            const id = e.target.dataset.id || e.target.closest('[data-id]')?.dataset.id;
            
            switch (action) {
                case 'view-appointment':
                    alert('View appointment details for ID: ' + id);
                    break;
                case 'complete-appointment':
                    if (confirm('Mark this appointment as completed?')) {
                        const appointmentRow = document.querySelector(`tr[data-appointment-id="${id}"]`);
                        if (appointmentRow) {
                            const statusElement = appointmentRow.querySelector('.status');
                            updateStatus(id, 'completed', statusElement);
                        }
                    }
                    break;
                case 'view-sale':
                    alert('View sale details for ID: ' + id);
                    break;
                case 'print-receipt':
                    printReceipt(id);
                    break;
            }
        }
    });

    // Filter tab event listeners
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('filter-tab')) {
            const filter = e.target.dataset.filter;
            if (filter) {
                document.querySelectorAll('.filter-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                e.target.classList.add('active');
                filterAppointments(filter);
            }
        }
    });

    // Status dropdown event listeners
    document.addEventListener('change', function(e) {
        if (e.target.tagName === 'SELECT' && e.target.classList.contains('status')) {
            const appointmentId = e.target.closest('tr').querySelector('[data-action="view-appointment"]')?.dataset.id;
            if (appointmentId) {
                updateStatus(appointmentId, e.target.value, e.target);
            }
        }
    });

    console.log('Admin Dashboard fully initialized');
});



