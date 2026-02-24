// settings.js

document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('uploadBtn');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    const profileForm = document.getElementById('profileForm');

    // Trigger file input when upload button is clicked
    uploadBtn.addEventListener('click', function() {
        logoInput.click();
    });

    // Preview uploaded image
    logoInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Replace preview with new image
                logoPreview.innerHTML = `<img src="${e.target.result}" alt="Logo preview">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle form submission (simulated)
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // In a real application, you would send the data via fetch/AJAX
        alert('Settings saved successfully! (simulated)');
    });
});