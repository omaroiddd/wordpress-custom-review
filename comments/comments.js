document.addEventListener('DOMContentLoaded', function () {

    // ── Star rating label ───────────────────────
    const labels = { 1:'ضعيف', 2:'مقبول', 3:'جيد', 4:'جيد جداً', 5:'ممتاز' };
    document.querySelectorAll('.stars-wrapper input').forEach(input => {
        input.addEventListener('change', function () {
            document.getElementById('rating-label').textContent = labels[this.value];
        });
    });

    // ── Char counter ────────────────────────────
    const textarea = document.getElementById('comment');
    const counter  = document.getElementById('char-num');
    if (textarea && counter) {
        textarea.addEventListener('input', () => {
            counter.textContent = textarea.value.length;
        });
    }

    // ── Photo preview ───────────────────────────
    const photoInput  = document.getElementById('photo');
    const dropZone    = document.getElementById('photo-drop-zone');
    const preview     = document.getElementById('photo-preview');
    const previewImg  = document.getElementById('preview-img');
    const removeBtn   = document.getElementById('remove-photo');

    if (photoInput) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('حجم الصورة يجب أن يكون أقل من 2MB');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src  = e.target.result;
                dropZone.style.display  = 'none';
                preview.style.display   = 'block';
            };
            reader.readAsDataURL(file);
        });

        removeBtn.addEventListener('click', function () {
            photoInput.value        = '';
            previewImg.src          = '';
            preview.style.display   = 'none';
            dropZone.style.display  = 'flex';
        });
    }

    // ── Phone validation ────────────────────────
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('blur', function () {
            const valid = /^05[0-9]{8}$/.test(this.value);
            this.style.borderColor = this.value && !valid ? '#ef4444' : '';
        });
    }

});