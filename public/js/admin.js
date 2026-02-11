/* ========================================
   CDEM Solutions — Admin JavaScript
   ======================================== */

document.addEventListener('DOMContentLoaded', () => {

    // --- Sidebar toggle (mobile) ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar on outside click
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // --- Auto-generate slug from title ---
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput) {
        let userEditedSlug = slugInput.value !== '';

        slugInput.addEventListener('input', () => {
            userEditedSlug = slugInput.value !== '';
        });

        titleInput.addEventListener('input', () => {
            if (!userEditedSlug) {
                slugInput.value = slugify(titleInput.value);
            }
        });
    }

    function slugify(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '')
            .substring(0, 100);
    }

    // --- Tag suggestions ---
    const tagInput = document.getElementById('tags');
    const tagSuggestions = document.querySelectorAll('.tag-suggestion');

    tagSuggestions.forEach(btn => {
        btn.addEventListener('click', () => {
            if (!tagInput) return;
            const tag = btn.dataset.tag;
            const current = tagInput.value.split(',').map(t => t.trim()).filter(Boolean);
            if (!current.includes(tag)) {
                current.push(tag);
                tagInput.value = current.join(', ');
            }
        });
    });

    // --- Image upload ---
    const imageUpload = document.getElementById('imageUpload');
    const featuredImage = document.getElementById('featured_image');

    if (imageUpload && featuredImage) {
        imageUpload.addEventListener('change', async () => {
            const file = imageUpload.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            try {
                const response = await fetch('/admin/upload/image/', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.url) {
                    featuredImage.value = result.url;
                    // Update preview
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview';
                        featuredImage.parentElement.appendChild(preview);
                    }
                    preview.src = result.url;
                } else {
                    alert(result.error || 'Upload failed');
                }
            } catch {
                alert('Upload failed');
            }
        });
    }

    // --- Auto-dismiss flash messages ---
    document.querySelectorAll('.flash').forEach(flash => {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    });
});
