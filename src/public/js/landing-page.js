// Custom showForm function for the landing page
function showForm(action, url, statusData) {
    // Extract the base URL without the formToken parameter
    let baseUrl = url.split('?')[0];
    
    // Get the formToken from the URL if present
    let formToken = null;
    if (url.includes('formToken=')) {
        formToken = url.split('formToken=')[1];
    }
    
    // Set the appropriate HTTP method based on the action
    let method = 'GET';
    if (action === 'delete') {
        method = 'DELETE';
    }
    
    // Create XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open(method, baseUrl, true);
    
    // Set the form token in the Authorization header if available
    if (formToken) {
        xhr.setRequestHeader('Authorization', 'Bearer ' + formToken);
    }
    
    xhr.onload = function() {
        let content = xhr.response;
        
        // Update the form token if a fresh one is provided
        if (xhr.getResponseHeader('FreshToken') !== '' && xhr.getResponseHeader('FreshToken') !== null) {
            formToken = xhr.getResponseHeader('FreshToken');
        }
        
        try {
            // Try to parse as JSON
            content = JSON.parse(content);
            
            // If it's a message, handle it
            if (content.message) {
                handleHtmlData(content.message, 'formModalBody');
            } else {
                handleHtmlData(content, 'formModalBody');
            }
        } catch (e) {
            // If not JSON, treat as HTML
            handleHtmlData(content, 'formModalBody');
        }
        
        // If status data is provided (for enable/disable)
        if (statusData && (statusData.status === 'disabled' || statusData.status === 'up')) {
            // Find the status field in the form and set its value
            let statusField = document.querySelector('#monitoredSitesForm [name="status"]');
            if (statusField) {
                statusField.value = statusData.status;
                
                // Auto-submit the form
                setTimeout(function() {
                    if ($('#monitoredSitesForm').valid()) {
                        saveForm('monitoredSitesForm', baseUrl, 'message');
                        $('#formModal').modal('hide');
                    }
                }, 100);
            }
        } else {
            // Show the modal for regular edit/delete operations
            $('#formModal').modal('show');
        }
    };
    
    xhr.send(null);
}