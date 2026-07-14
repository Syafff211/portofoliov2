        // ── GANTI DENGAN URL API PHP ANDA ──
        const API_URL = 'https://portofolio-syafxx.pages.dev/api.php'; 

        // ── CONTACT FORM & MODAL ──
        const contactForm = document.getElementById('contact-form');
        const modal = document.getElementById('modal');
        const modalCloseBtn = document.getElementById('modal-close-btn');

        if (contactForm) {
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btnSubmit = contactForm.querySelector('.btn-primary');
                const originalText = btnSubmit.innerHTML;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                btnSubmit.disabled = true;

                const formData = new FormData(contactForm);
                formData.append('action', 'save_message');

                try {
                    const res = await fetch(API_URL, {
                        method: 'POST',
                        body: formData // Menggunakan FormData agar PHP bisa baca $_POST
                    });
                    const data = await res.json();

                    if (data.success) {
                        modal.classList.add('open');
                        contactForm.reset();
                    } else {
                        showToast(data.message || 'Gagal mengirim pesan');
                    }
                } catch (error) {
                    showToast('Gagal terhubung ke server. Cek koneksi internet Anda.');
                    console.error('Error:', error);
                } finally {
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                }
            });
        }

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', () => modal.classList.remove('open'));
        }
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('open');
            });
        }

        // ── LIKE BUTTON (TERHUBUNG KE API) ──
        const likeBtn = document.getElementById('like-btn');
        let currentLikes = 0;

        // Ambil jumlah like awal dari server
        async function fetchLikes() {
            try {
                const res = await fetch(`${API_URL}?action=get_likes`);
                const data = await res.json();
                if (data.success) {
                    currentLikes = data.count;
                    updateLikeUI(false);
                }
            } catch (e) { console.log('Gagal memuat likes'); }
        }
        fetchLikes(); // Jalankan saat halaman pertama kali dibuka

        if (likeBtn) {
            likeBtn.addEventListener('click', async () => {
                const isLiked = likeBtn.classList.contains('liked');
                const newLikedStatus = !isLiked;

                try {
                    const res = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=update_like&liked=${newLikedStatus}`
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        currentLikes = data.count;
                        updateLikeUI(newLikedStatus);
                    }
                } catch (e) {
                    showToast('Gagal menyimpan like');
                }
            });
        }

        function updateLikeUI(isLiked) {
            const icon = likeBtn.querySelector('i');
            const text = likeBtn.querySelector('span');
            if (isLiked) {
                likeBtn.classList.add('liked');
                icon.classList.remove('far');
                icon.classList.add('fas');
                text.innerText = `Disukai (${currentLikes})`;
            } else {
                likeBtn.classList.remove('liked');
                icon.classList.remove('fas');
                icon.classList.add('far');
                text.innerText = currentLikes > 0 ? `Suka (${currentLikes})` : 'Apakah Anda menyukai desain ini?';
            }
        }
