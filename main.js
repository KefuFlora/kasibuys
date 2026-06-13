// Dropdown toggle
document.addEventListener('DOMContentLoaded', function () {

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Image preview on listing form
    const imageInput = document.getElementById('listing-image');
    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:8px;margin:5px;';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Confirm delete
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });
});
