function showError(input, message) {
    input.classList.add('is-invalid');
    let errorDiv = input.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

function clearError(input) {
    input.classList.remove('is-invalid');
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.style.display = 'none';
    }
}

function validateType(input) {
    if (!input.value) {
        showError(input, 'Type is required');
        return false;
    } else {
        clearError(input);
        return true;
    }
}

function validateEmail(input) {
    if (!input.value) {
        showError(input, input.dataset.errorRequired);
        return false;
    } else if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(input.value)) {
        showError(input, input.dataset.errorFormat);
        return false;
    } else {
        clearError(input);
        return true;
    }
}

function validatePhone(input) {
    // Remove non-digits first
    input.value = input.value.replace(/[^0-9]/g, '');
    
    if (!input.value) {
        showError(input, input.dataset.errorRequired);
        return false;
    } else if (input.value.length !== 8) {
        showError(input, input.dataset.errorLength);
        return false;
    } else if (!/^\d+$/.test(input.value)) {
        showError(input, input.dataset.errorFormat);
        return false;
    } else {
        clearError(input);
        return true;
    }
}

function validateLogo(input) {
    // If not required and no file selected, it's valid
    if (input.required === false && (!input.files || input.files.length === 0)) {
        clearError(input);
        return true;
    }
    
    // Required check
    if (input.required && (!input.files || input.files.length === 0)) {
        showError(input, input.dataset.errorRequired);
        return false;
    }
    
    // If a file is selected, validate it regardless of required status
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        const validTypes = ['image/jpeg', 'image/png'];
        
        if (!validTypes.includes(file.type)) {
            showError(input, input.dataset.errorType);
            return false;
        } else if (file.size > 1024 * 1024) { // 1MB
            showError(input, input.dataset.errorSize);
            return false;
        } else {
            clearError(input);
            return true;
        }
    }
    
    // Default case - if we get here, it's valid
    clearError(input);
    return true;
}

function validateSelect(input) {
    if (!input.value) {
        showError(input, input.dataset.errorRequired || 'This field is required');
        return false;
    } else {
        clearError(input);
        return true;
    }
}

function validateDate(input) {
    if (input.required && !input.value) {
        showError(input, input.dataset.errorRequired || 'Date is required');
        return false;
    }
    
    if (input.value) {
        const selectedDate = new Date(input.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (isNaN(selectedDate.getTime())) {
            showError(input, input.dataset.errorFormat || 'Please enter a valid date');
            return false;
        }
        
        // Start date validation - must be today or in the future
        // Apply to both partnership and contract forms
        const formName = input.closest('form').getAttribute('name');
        if (input.name.includes('date_debut') && selectedDate < today) {
            showError(input, input.dataset.errorFuture || 'Start date must be today or in the future');
            return false;
        }
        
        // End date validation - must be after start date
        if (input.name.includes('date_fin')) {
            const startDateInput = document.querySelector('[name$="[date_debut]"]');
            if (startDateInput && startDateInput.value) {
                const startDate = new Date(startDateInput.value);
                if (selectedDate <= startDate) {
                    showError(input, input.dataset.errorAfter || 'End date must be after start date');
                    return false;
                }
            }
        }
    }
    
    clearError(input);
    return true;
}

// Form validation function
function validatePartnerForm(form) {
    let isValid = true;
    
    // Get all form fields
    const typeField = form.querySelector('[name="partner[type_partner]"]');
    const emailField = form.querySelector('[name="partner[email]"]');
    const phoneField = form.querySelector('[name="partner[phone_Number]"]');
    const logoField = form.querySelector('[name="partner[logoFile]"]');
    
    // Determine if this is a new partner form or edit form
    // For edit form, we don't require the logo to be present
    const isEditForm = document.querySelector('.card-header')?.textContent.includes('Edit');
    
    // Validate each field and capture the result
    const typeValid = validateType(typeField);
    const emailValid = validateEmail(emailField);
    const phoneValid = validatePhone(phoneField);
    
    // For logo validation, handle differently for new vs edit form
    let logoValid = true;
    if (logoField) {
        if (isEditForm) {
            // For edit form, validate logo only if one is provided
            logoValid = validateLogo(logoField);
        } else {
            // For new form, logo is required
            logoValid = validateLogo(logoField);
        }
    }
    
    // Form is valid only if all fields are valid
    return typeValid && emailValid && phoneValid && logoValid;
}

function validatePartnershipForm(form) {
    let isValid = true;
    
    // Get all form fields
    const partnerField = form.querySelector('[name$="[id_partner]"]');
    const eventField = form.querySelector('[name$="[id_event]"]');
    const startDateField = form.querySelector('[name$="[date_debut]"]');
    const endDateField = form.querySelector('[name$="[date_fin]"]');
    const termsField = form.querySelector('[name$="[terms]"]');
    
    // Validate each field
    const partnerValid = validateSelect(partnerField);
    const eventValid = validateSelect(eventField);
    const startDateValid = validateDate(startDateField);
    const endDateValid = validateDate(endDateField);
    
    // Terms validation
    let termsValid = true;
    if (termsField) {
        if (!termsField.value.trim()) {
            showError(termsField, termsField.dataset.errorRequired || 'Terms and conditions are required');
            termsValid = false;
        } else {
            clearError(termsField);
        }
    }
    
    // Form is valid only if all fields are valid
    return partnerValid && eventValid && startDateValid && endDateValid && termsValid;
}

function validateContractForm(form) {
    let isValid = true;
    
    // Get all form fields
    const partnerField = form.querySelector('[name$="[id_partner]"]');
    const partnershipField = form.querySelector('[name$="[id_partnership]"]');
    const startDateField = form.querySelector('[name$="[date_debut]"]');
    const endDateField = form.querySelector('[name$="[date_fin]"]');
    const termsField = form.querySelector('[name$="[terms]"]');
    const statusField = form.querySelector('[name$="[status]"]');
    
    // Validate each field
    const partnerValid = validateSelect(partnerField);
    const partnershipValid = validateSelect(partnershipField);
    const startDateValid = validateDate(startDateField);
    const endDateValid = validateDate(endDateField);
    
    // Terms validation
    let termsValid = true;
    if (termsField) {
        if (!termsField.value.trim()) {
            showError(termsField, termsField.dataset.errorRequired || 'Contract terms are required');
            termsValid = false;
            
            // Create a more visible error below the field
            let errorAlert = document.querySelector('#terms-error');
            if (!errorAlert) {
                errorAlert = document.createElement('div');
                errorAlert.id = 'terms-error';
                errorAlert.className = 'alert alert-danger mt-2';
                errorAlert.textContent = 'Contract terms cannot be empty.';
                termsField.parentNode.appendChild(errorAlert);
            }
        } else {
            clearError(termsField);
            // Remove any existing visible error
            const errorAlert = document.querySelector('#terms-error');
            if (errorAlert) {
                errorAlert.remove();
            }
        }
    }
    
    // Status validation
    let statusValid = true;
    if (statusField) {
        if (!statusField.value) {
            showError(statusField, statusField.dataset.errorRequired || 'Status is required');
            statusValid = false;
        } else {
            clearError(statusField);
            
            // Check if status is 'expired'
            if (statusField.value === 'expired') {
                // We'll show a warning in the form but won't block submission
                // The server-side validation will handle the actual check against partnership end date
                const formErrorContainer = document.querySelector('#status-warning');
                if (!formErrorContainer) {
                    const warningMessage = document.createElement('div');
                    warningMessage.id = 'status-warning';
                    warningMessage.className = 'alert alert-warning mt-2';
                    warningMessage.textContent = 'Note: Status can only be set to Expired if the partnership has ended.';
                    statusField.parentNode.appendChild(warningMessage);
                }
            }
        }
    }
    
    // Form is valid only if all fields are valid
    return partnerValid && partnershipValid && startDateValid && endDateValid && termsValid && statusValid;
}

// Add event listener when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find the partner form
    const form = document.querySelector('form[name="partner"]');
    
    if (form) {
        // Add submit event listener
        form.addEventListener('submit', function(event) {
            // Validate the form
            if (!validatePartnerForm(this)) {
                // Prevent form submission if validation fails
                event.preventDefault();
                event.stopPropagation();
                
                // Show a message at the top of the form
                let errorAlert = document.getElementById('form-error-alert');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.id = 'form-error-alert';
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.textContent = 'Please fix all errors before submitting the form.';
                    form.insertAdjacentElement('afterbegin', errorAlert);
                }
                
                // Scroll to the top of the form
                window.scrollTo({
                    top: form.getBoundingClientRect().top + window.pageYOffset - 20,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Find the partnership form
    const partnershipForm = document.querySelector('form[name="partnership"]');
    
    if (partnershipForm) {
        // Add event listeners for date fields to validate as they change
        const startDateField = partnershipForm.querySelector('[name$="[date_debut]"]');
        const endDateField = partnershipForm.querySelector('[name$="[date_fin]"]');
        
        if (startDateField) {
            startDateField.addEventListener('change', function() {
                validateDate(this);
            });
        }
        
        if (endDateField) {
            endDateField.addEventListener('change', function() {
                validateDate(this);
            });
        }
        
        // Add submit event listener
        partnershipForm.addEventListener('submit', function(event) {
            // Validate the form
            if (!validatePartnershipForm(this)) {
                // Prevent form submission if validation fails
                event.preventDefault();
                event.stopPropagation();
                
                // Show a message at the top of the form
                let errorAlert = document.getElementById('form-error-alert');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.id = 'form-error-alert';
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.textContent = 'Please fix all errors before submitting the form.';
                    partnershipForm.insertAdjacentElement('afterbegin', errorAlert);
                }
                
                // Scroll to the top of the form
                window.scrollTo({
                    top: partnershipForm.getBoundingClientRect().top + window.pageYOffset - 20,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Find the contract form
    const contractForm = document.querySelector('form[name="contract"]');
    
    if (contractForm) {
        // Add event listeners for date fields to validate as they change
        const startDateField = contractForm.querySelector('[name$="[date_debut]"]');
        const endDateField = contractForm.querySelector('[name$="[date_fin]"]');
        
        if (startDateField) {
            startDateField.addEventListener('change', function() {
                validateDate(this);
                // When start date changes, revalidate end date
                if (endDateField && endDateField.value) {
                    validateDate(endDateField);
                }
            });
        }
        
        if (endDateField) {
            endDateField.addEventListener('change', function() {
                validateDate(this);
            });
        }
        
        // Add submit event listener
        contractForm.addEventListener('submit', function(event) {
            // Validate the form
            if (!validateContractForm(this)) {
                // Prevent form submission if validation fails
                event.preventDefault();
                event.stopPropagation();
                
                // Show a message at the top of the form
                let errorAlert = document.getElementById('form-error-alert');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.id = 'form-error-alert';
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.textContent = 'Please fix all errors before submitting the form.';
                    contractForm.insertAdjacentElement('afterbegin', errorAlert);
                }
                
                // Scroll to the top of the form
                window.scrollTo({
                    top: contractForm.getBoundingClientRect().top + window.pageYOffset - 20,
                    behavior: 'smooth'
                });
            }
        });
    }
}); 