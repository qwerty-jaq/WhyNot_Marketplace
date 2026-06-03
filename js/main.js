document.addEventListener('DOMContentLoaded', function() {

    //Boodstrap form validation
    document.querySelectorAll('.needs-validation').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    //Confirm delete action
    document.querySelectorAll('.confirm-delete').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm('Are your sure? This action cannot be undone.')) e.preventDefault();
        });
    });

    //Format prices to 2 decimals
    document.querySelectorAll('.price').forEach(function(input) {
        input.addEventListener('blur', function() {
            const v = parseFloat(this.value);
            if (!isNaN(v)) this.value = v.toFixed(2);
        });
    });

    //Image preview on sell page
    const imgInput = document.getElementById('imageInput');
    const imgPreview = document.getElementById('imagePreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    //Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            const btn = alert.querySelector('.btn-close');
            if (btn) btn.click();
        }, 5000);
    });
    
});

