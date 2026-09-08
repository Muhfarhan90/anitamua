<div id="galleryLightboxModal" onclick="closeGalleryLightbox()" style="display:none; position:fixed; inset:0; z-index:1100; background:rgba(0,0,0,.9); align-items:center; justify-content:center; padding:2rem;">
    <button type="button" onclick="event.stopPropagation();closeGalleryLightbox()" aria-label="Tutup" style="position:absolute; top:20px; right:30px; background:none; border:none; color:#fff; font-size:2.5rem; cursor:pointer; line-height:1;">&times;</button>
    <div onclick="event.stopPropagation()" style="max-width:900px; width:100%; text-align:center;">
        <div style="position:relative;">
            <img id="galleryLightboxImage" src="" alt="" style="max-width:100%; max-height:72vh; border-radius:12px; display:block; margin:0 auto; object-fit:contain;">
            <button id="galleryLightboxPrev" type="button" aria-label="Foto sebelumnya" style="position:absolute; top:50%; left:12px; transform:translateY(-50%); width:40px; height:40px; border:0; border-radius:999px; background:rgba(0,0,0,.5); color:#fff; cursor:pointer;"><i class="fas fa-chevron-left"></i></button>
            <button id="galleryLightboxNext" type="button" aria-label="Foto berikutnya" style="position:absolute; top:50%; right:12px; transform:translateY(-50%); width:40px; height:40px; border:0; border-radius:999px; background:rgba(0,0,0,.5); color:#fff; cursor:pointer;"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div id="galleryLightboxDots" class="flex justify-center gap-2 mt-4"></div>
        <p id="galleryLightboxCaption" class="mt-3" style="color:#fff; font-size:1rem;"></p>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('galleryLightboxModal');
        const image = document.getElementById('galleryLightboxImage');
        const caption = document.getElementById('galleryLightboxCaption');
        const dots = document.getElementById('galleryLightboxDots');
        const previous = document.getElementById('galleryLightboxPrev');
        const next = document.getElementById('galleryLightboxNext');
        let photos = [];
        let index = 0;

        const show = (nextIndex) => {
            index = (nextIndex + photos.length) % photos.length;
            image.src = photos[index];
            [...dots.children].forEach((dot, dotIndex) => {
                dot.style.background = dotIndex === index ? '#fff' : 'rgba(255,255,255,.45)';
            });
        };

        window.openGalleryLightbox = (items, title) => {
            photos = items;
            index = 0;
            caption.textContent = title || '';
            dots.replaceChildren(...photos.map((_, dotIndex) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.ariaLabel = `Tampilkan foto ${dotIndex + 1}`;
                dot.style.cssText = 'width:8px;height:8px;border:0;border-radius:999px;cursor:pointer;padding:0;';
                dot.addEventListener('click', () => show(dotIndex));
                return dot;
            }));
            previous.style.display = next.style.display = photos.length > 1 ? '' : 'none';
            dots.style.display = photos.length > 1 ? 'flex' : 'none';
            show(0);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };

        window.closeGalleryLightbox = () => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-gallery-lightbox]');
            if (!trigger) return;
            openGalleryLightbox(JSON.parse(atob(trigger.dataset.galleryPhotos)), trigger.dataset.galleryTitle);
        });

        previous.addEventListener('click', () => show(index - 1));
        next.addEventListener('click', () => show(index + 1));
        document.addEventListener('keydown', (event) => {
            const trigger = event.target.closest('[data-gallery-lightbox]');
            if (trigger && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                openGalleryLightbox(JSON.parse(atob(trigger.dataset.galleryPhotos)), trigger.dataset.galleryTitle);
            }
            if (modal.style.display !== 'flex') return;
            if (event.key === 'Escape') closeGalleryLightbox();
            if (event.key === 'ArrowLeft') show(index - 1);
            if (event.key === 'ArrowRight') show(index + 1);
        });
    })();
</script>
