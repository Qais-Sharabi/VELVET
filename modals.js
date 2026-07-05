// modals.js — VELVET Pop-up Handlers

// ── About Modal ───────────────────────────────────────────────
function loadAboutModal() {
    if (document.getElementById('aboutModal')) return;
    const aboutModalHTML = `
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-2 mt-0">
            <div class="modal-header border-0 mb-0">
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="container-fluid">
                    <div class="row g-0">
                        <div class="col-md-6 d-none d-md-block mb-4 mt-0">
                            <div class="about-image-placeholder h-90 min-vh-50 d-flex align-items-center justify-content-center bg-light mt-5"></div>
                        </div>
                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center">
                            <p class="text-muted small text-uppercase mb-2 tracking-widest">Established 2026</p>
                            <h2 class="fw-bold mb-4">VELVET STORY</h2>
                            <p class="text-muted lh-lg mb-2">
                                Born from a passion for minimalist design and premium materials, <strong>VELVET</strong> was created to
                                redefine modern essentials. We believe in quality over quantity, focusing on pieces that transcend seasons.
                            </p>
                            <p class="text-muted lh-lg mb-2">
                                For us, true elegance is found in the details. It's the soft touch of a fine fabric and the clean
                                lines of a perfect fit. We believe that what you wear should be an extension of your confidence—quiet,
                                refined, and effortless.
                            </p>
                            <p class="text-muted lh-lg mb-3">Our goal is simple: to create a wardrobe that empowers your natural presence.</p>
                            <div class="d-flex gap-4">
                                <h6 class="signature-font">NOOR ELSAID</h6>
                                <h6 class="signature-font">QAIS SHARABI</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', aboutModalHTML);
}

// ── Search Modal ──────────────────────────────────────────────
function loadSearchModal() {
    if (document.getElementById('searchModal')) return;
    const searchHTML = `
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-0 px-4 pt-4">
                <button type="button" class="btn-plain border-0 bg-transparent" data-bs-dismiss="modal">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="flex-grow-1 mx-3">
                    <div class="search-input-container rounded-pill border px-3 py-2 d-flex align-items-center">
                        <input type="text" id="ajaxSearchInput" class="form-control border-0 shadow-none ps-0"
                               placeholder="Search products…" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="modal-body px-4">
                <div class="search-section mt-4">
                    <h6 class="fw-bold mb-3">Suggested</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="discovery-tag py-2 px-3 rounded text-dark small border-0 bg-light" onclick="runSearch('dress')">Dress</span>
                        <span class="discovery-tag py-2 px-3 rounded text-dark small border-0 bg-light" onclick="runSearch('shirt')">Shirt</span>
                        <span class="discovery-tag py-2 px-3 rounded text-dark small border-0 bg-light" onclick="runSearch('skirt')">Skirt</span>
                        <span class="discovery-tag py-2 px-3 rounded text-dark small border-0 bg-light" onclick="runSearch('blouse')">Blouse</span>
                            <span class="discovery-tag py-2 px-3 rounded text-dark small border-0 bg-light" onclick="runSearch('summer')">Summer</span>
                    </div>
                </div>
                <div id="searchResults" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', searchHTML);

    // Live search
    setTimeout(() => {
        const inp = document.getElementById('ajaxSearchInput');
        if (inp) {
            inp.addEventListener('input', function() {
                const q = this.value.trim();
                if (q.length < 2) { document.getElementById('searchResults').innerHTML = ''; return; }
                runSearch(q);
            });
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') window.location.href = 'shop.php?q=' + encodeURIComponent(this.value.trim());
            });
        }
        document.querySelectorAll('.discovery-tag').forEach(tag => {
            tag.style.cursor = 'pointer';
        });
    }, 200);
}

function runSearch(q) {
    const resultsEl = document.getElementById('searchResults');
    if (!resultsEl) return;
    resultsEl.innerHTML = '<div class="text-muted small py-3 text-center"><span class="spinner-border spinner-border-sm me-2"></span>Searching…</div>';
    fetch('get_search_results.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                resultsEl.innerHTML = '<p class="text-muted small text-center py-3">No results for "' + q + '".<br><a href="shop.php?q=' + encodeURIComponent(q) + '">View all results</a></p>';
                return;
            }
            resultsEl.innerHTML = '<h6 class="fw-bold mb-3">Results for "' + q + '"</h6>' +
                data.map(p => `
                <a href="product_details.php?slug=${p.slug}" class="d-flex align-items-center gap-3 mb-3 text-decoration-none text-dark border-bottom pb-2">
                    <img src="${p.image_url || 'images/general/placeholder.jpg'}" style="width:52px;height:65px;object-fit:cover;border:1px solid #eee;">
                    <div>
                        <div class="fw-bold small">${p.name}</div>
                        <div class="text-muted small">${p.is_sale && p.sale_price ? '₪'+parseFloat(p.sale_price).toFixed(2) : '₪'+parseFloat(p.base_price).toFixed(2)}</div>
                    </div>
                </a>`).join('') +
                `<a href="shop.php?q=${encodeURIComponent(q)}" class="btn btn-outline-dark btn-sm rounded-0 w-100 mt-2">See All Results</a>`;
        })
        .catch(() => { resultsEl.innerHTML = '<p class="text-muted small text-center">Search unavailable.</p>'; });
}

// ── Profile Modal — server-rendered via PHP data attrs ────────
function loadProfileModal() {
    if (document.getElementById('profile-modal')) return;
    // Read session data injected by PHP into the page
    const isLoggedIn = (window.VELVET_SESSION && window.VELVET_SESSION.loggedIn) || false;
    const userName   = (window.VELVET_SESSION && window.VELVET_SESSION.name)     || 'Guest';
    const userLetter = (window.VELVET_SESSION && window.VELVET_SESSION.letter)   || '';
    const isAdmin    = (window.VELVET_SESSION && window.VELVET_SESSION.isAdmin)   || false;

    let inner = '';
    if (isLoggedIn) {
        inner = `
        <div class="profile-header">
            <div class="profile-avatar">${userLetter}</div>
            <h3>${userName}</h3>
            <span class="member-badge">VELVET MEMBER</span>
        </div>
        <div class="profile-menu">
            ${isAdmin ? `
            <a href="Manager.php" class="menu-item" >
                <i class="fa-solid fa fa-wrench"></i>
                <div><p>Manager Dashboard</p><span style="color:darkred; font-weight: bold;">Manage the store</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>` : ''}
            <a href="order-tracking.php" class="menu-item">
                <i class="fa-solid fa-location-dot"></i>
                <div><p>Track Order</p><span>Check your delivery status</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>
            <a href="shopping%20bag.php" class="menu-item">
                <i class="fa-solid fa-bag-shopping"></i>
                <div><p>My Bag</p><span>View cart items</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>
        </div>
        <a href="logout.php" class="logout-btn text-decoration-none d-block text-center" >
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>`;
    } else {
        inner = `
        <div class="profile-header">
            <div class="profile-avatar"><i class="fa-regular fa-user"></i></div>
            <h3>Hello, Guest</h3>
            <span class="member-badge">Join Velvet</span>
        </div>
        <div class="profile-menu">
            <a href="login.php" class="menu-item">
                <i class="fa-solid fa-right-to-bracket"></i>
                <div><p>Sign In</p><span>Access your account</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>
            <a href="signup.php" class="menu-item">
                <i class="fa-solid fa-user-plus"></i>
                <div><p>Create Account</p><span>Join Velvet today</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>
            <a href="order-tracking.php" class="menu-item">
                <i class="fa-solid fa-magnifying-glass"></i>
                <div><p>Track Order</p><span>Look up any order</span></div>
                <i class="fa-solid fa-chevron-right ms-auto"></i>
            </a>
        </div>`;
    }

    const profileHTML = `
    <div id="profile-modal" class="modal-overlay" style="display:none;">
        <div class="modal-card profile-card ms-auto me-md-5 mx-auto">
            <button class="close-btn" onclick="toggleProfileModal()">&times;</button>
            ${inner}
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', profileHTML);
}

// ── Contact Modal ─────────────────────────────────────────────
function loadContactModal() {
    if (document.getElementById('contactModal')) return;
    const contactHTML = `
    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-body p-5">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold tracking-widest text-uppercase">Contact Us</h4>
                        <p class="text-muted small">We would <span class="special-word"> love </span> to hear from you.</p>
                    </div>
                    <form id="contactForm" action="contact_process.php" method="POST">
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control rounded-0 border-0 bg-light" placeholder="YOUR NAME" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control rounded-0 border-0 bg-light" placeholder="YOUR EMAIL" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="message" class="form-control rounded-0 border-0 bg-light" rows="4" placeholder="YOUR MESSAGE" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-0 py-3 tracking-widest fw-bold">SEND MESSAGE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', contactHTML);
}

// ── Toggle Profile Slide-in ───────────────────────────────────
function toggleProfileModal() {
    const modal = document.getElementById('profile-modal');
    if (!modal) return;
    modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
}

window.addEventListener('click', function(e) {
    const m = document.getElementById('profile-modal');
    if (m && e.target === m) m.style.display = 'none';
});

// ── Contact form AJAX submit ──────────────────────────────────
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'contactForm') {
        e.preventDefault();
        const form = e.target;
        const btn  = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>SENDING...';
        fetch('contact_process.php', { method:'POST', body: new FormData(form) })
            .then(() => {
                form.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <h5 class="fw-bold text-uppercase">Message Received</h5>
                    <p class="text-muted small mt-2">Thank you for reaching out! We'll reply shortly.</p>
                </div>`;
            })
            .catch(() => { btn.disabled = false; btn.innerHTML = 'SEND MESSAGE'; });
    }
});
