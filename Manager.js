
// ════════════════════════════════════════════════════════════
//  SIDEBAR NAVIGATION
// ════════════════════════════════════════════════════════════
document.querySelectorAll('.subdiv[data-target]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.subdiv[data-target]').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.getAttribute('data-target')).classList.add('active');
        document.getElementById('page-title').innerText = this.innerText.trim();
    });
});

// ════════════════════════════════════════════════════════════
//  SEARCH — works on tables AND product cards
// ════════════════════════════════════════════════════════════
function setupTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase().trim();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}
setupTableSearch('order-search',    'orders-table');
setupTableSearch('customer-search', 'customers-table');

// Product card search
const productSearch = document.getElementById('product-search');
if (productSearch) {
    productSearch.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase().trim();
        document.querySelectorAll('.admin-product-card').forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}

// ════════════════════════════════════════════════════════════
//  ORDER VIEW MODAL
// ════════════════════════════════════════════════════════════
async function viewOrder(orderId) {
    const overlay = document.getElementById('order-modal-overlay');
    const body    = document.getElementById('order-modal-body');
    const title   = document.getElementById('order-modal-title');

    overlay.classList.add('open');
    body.innerHTML = '<p style="text-align:center;padding:30px;color:#888;">Loading…</p>';
    title.innerText = 'Order #' + orderId;

    try {
        const r    = await fetch('Manager.php?ajax_action=get_order&order_id=' + orderId);
        const data = await r.json();

        if (!data || !data.id) {
            body.innerHTML = '<p style="color:red;padding:20px;">Could not load order data.</p>';
            return;
        }

        const statuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
        const opts = statuses.map(s =>
            `<option value="${s}" ${data.status === s ? 'selected' : ''}>${cap(s)}</option>`
        ).join('');

        const itemRows = (data.items || []).map(item => `
            <tr>
                <td><img src="${item.image_url || 'placeholder.jpg'}" alt=""></td>
                <td><b>${esc(item.product_name)}</b><br>
                    <small style="color:gray;">${item.size || ''}${item.color ? ' / '+item.color : ''}</small></td>
                <td>${item.quantity}</td>
                <td>₪${parseFloat(item.unit_price).toFixed(2)}</td>
                <td><b>₪${(item.quantity * item.unit_price).toFixed(2)}</b></td>
            </tr>
        `).join('') || '<tr><td colspan="5" style="text-align:center;color:#aaa;padding:12px;">No items recorded.</td></tr>';

        const trackRows = (data.tracking || []).map(t => `
            <div class="tracking-event">
                <div class="tracking-dot"></div>
                <div>
                    <b>${esc(t.event_label)}</b>
                    <div style="color:gray;font-size:12px;">${esc(t.description||'')}</div>
                    <div style="color:#aaa;font-size:11px;">${t.happened_at}</div>
                </div>
            </div>
        `).join('') || '<p style="color:#aaa;font-size:13px;">No tracking events yet.</p>';

        body.innerHTML = `
            <div class="order-info-grid">
                <div><b>Customer</b>${esc(data.full_name)}</div>
                <div><b>Email</b>${esc(data.email)}</div>
                <div><b>Phone</b>${esc(data.phone||'—')}</div>
                <div><b>Payment</b>${data.payment_method}
                    &nbsp;<span class="status ${data.payment_status}">${cap(data.payment_status)}</span></div>
                <div><b>Shipping To</b>
                    ${data.ship_name ? esc(data.ship_name)+', ' : ''}
                    ${data.city ? esc(data.city)+' — ' : ''}
                    ${esc(data.street||'—')}</div>
                <div><b>Order Date</b>${data.ordered_at}</div>
                <div><b>Subtotal</b>₪${parseFloat(data.subtotal).toFixed(2)}</div>
                <div><b>Discount</b>₪${parseFloat(data.discount_amount||0).toFixed(2)}</div>
                <div><b>Shipping</b>₪${parseFloat(data.shipping_fee).toFixed(2)}</div>
                <div><b>Total</b>
                    <span style="font-size:16px;font-weight:bold;">
                        ₪${parseFloat(data.total_amount).toFixed(2)}
                    </span>
                </div>
            </div>
            <h4>Items</h4>
            <table class="order-items-table">
                <thead>
                    <tr><th>Image</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>${itemRows}</tbody>
            </table>
            <h4>Update Order Status</h4>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:22px;">
                <select class="status-select" id="status-select-${data.id}">${opts}</select>
                <button class="update-status-btn" onclick="updateOrderStatus(${data.id})">
                    <i class="fas fa-save"></i> Update
                </button>
            </div>
            <h4>Tracking Timeline</h4>
            <div class="tracking-timeline">${trackRows}</div>
        `;
    } catch (err) {
        body.innerHTML = '<p style="color:red;padding:20px;">Error: ' + err.message + '</p>';
    }
}

function closeOrderModal() {
    document.getElementById('order-modal-overlay').classList.remove('open');
}
document.getElementById('order-modal-overlay').addEventListener('click', function (e) {
    if (e.target === this) closeOrderModal();
});

async function updateOrderStatus(orderId) {
    const select    = document.getElementById('status-select-' + orderId);
    const newStatus = select.value;
    const fd        = new FormData();
    fd.append('ajax_action', 'update_order_status');
    fd.append('order_id',    orderId);
    fd.append('status',      newStatus);

    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            // Update badge in orders table live
            const badge = document.getElementById('status-badge-' + orderId);
            if (badge) {
                badge.className = 'status ' + newStatus;
                badge.innerText = cap(newStatus);
            }
            alert('✅ Order #' + orderId + ' → ' + cap(newStatus));
            closeOrderModal();
        } else {
            alert('❌ ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    }
}

// ════════════════════════════════════════════════════════════
//  CUSTOMERS — toggle active / inactive
// ════════════════════════════════════════════════════════════
async function toggleCustomer(userId, currentState) {
    const action = currentState == 1 ? 'deactivate' : 'activate';
    if (!confirm('Are you sure you want to ' + action + ' this customer?')) return;

    const fd = new FormData();
    fd.append('ajax_action', 'toggle_customer');
    fd.append('user_id',     userId);

    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            const badge = document.getElementById('customer-status-' + userId);
            const btn   = badge.closest('tr').querySelector('.view-btn');
            if (data.is_active) {
                badge.className = 'status delivered';
                badge.innerText = 'Active';
                btn.innerText   = 'Deactivate';
                btn.setAttribute('onclick', 'toggleCustomer(' + userId + ', 1)');
            } else {
                badge.className = 'status cancelled';
                badge.innerText = 'Inactive';
                btn.innerText   = 'Activate';
                btn.setAttribute('onclick', 'toggleCustomer(' + userId + ', 0)');
            }
        } else {
            alert('❌ ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    }
}

// ════════════════════════════════════════════════════════════
//  SETTINGS — save all to DB
// ════════════════════════════════════════════════════════════
async function saveAllSettings() {
    const btn = document.querySelector('.save-settings-btn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    const fd = new FormData();
    fd.append('store_name',          document.getElementById('set-store-name').value)
    fd.append('support_phone',       document.getElementById('set-phone').value);
    fd.append('support_email',       document.getElementById('set-email').value);
    fd.append('instagram_url',       document.getElementById('set-insta').value);
    fd.append('facebook_url',        document.getElementById('set-fb').value);
    fd.append('low_stock_threshold', document.getElementById('set-low-stock').value);
    fd.append('shipping_fee',        document.getElementById('set-shipping-fee').value);
    fd.append('free_shipping_above', document.getElementById('set-free-ship').value);
    fd.append('cod_enabled',         document.getElementById('set-cod').checked ? 1 : 0);
    fd.append('maintenance_mode',    document.getElementById('set-maintenance').checked ? 1 : 0);

    const accent = document.querySelector('input[name="accent"]:checked');
    if (accent) fd.append('accent_color', accent.value);

    try {
        const r      = await fetch('save_settings.php', { method: 'POST', body: fd });
        const result = await r.text();
        if (result.trim() === 'SUCCESS') {
            alert('✅ Settings saved!');
        } else {
            alert('❌ Error saving settings:\n' + result);
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
    }
}

// ════════════════════════════════════════════════════════════
//  DARK MODE
// ════════════════════════════════════════════════════════════
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('velvet_dark', isDark ? '1' : '0');
    // Sync toggle checkbox
    const t = document.getElementById('theme-toggle');
    if (t) t.checked = isDark;
}

// Restore on load (also done inline in Manager.php <script> but kept here for safety)
(function () {
    if (localStorage.getItem('velvet_dark') === '1') {
        document.body.classList.add('dark-mode');
        const t = document.getElementById('theme-toggle');
        if (t) t.checked = true;
    }
})();

// ════════════════════════════════════════════════════════════
//  ACCENT COLOUR — changes ENTIRE theme
//  Sets CSS variable --accent which dark_mode.css wires to
//  every button, highlight, card border, etc.
// ════════════════════════════════════════════════════════════
function setAccent(color) {
    document.documentElement.style.setProperty('--accent', color);
    // Derive a darker shade for hover states
    document.documentElement.style.setProperty('--accent-dark', shadeColor(color, -20));
    // Derive shadow
    const rgb = hexToRgb(color);
    if (rgb) {
        document.documentElement.style.setProperty(
            '--accent-shadow', `rgba(${rgb.r},${rgb.g},${rgb.b},0.3)`
        );
    }
    // Save to localStorage so it persists across page loads
    localStorage.setItem('velvet_accent', color);
}

// Restore accent on load
(function () {
    const saved = localStorage.getItem('velvet_accent');
    if (saved) {
        document.documentElement.style.setProperty('--accent', saved);
        const rgb = hexToRgb(saved);
        if (rgb) {
            document.documentElement.style.setProperty('--accent-dark', shadeColor(saved, -20));
            document.documentElement.style.setProperty(
                '--accent-shadow', `rgba(${rgb.r},${rgb.g},${rgb.b},0.3)`
            );
        }
        // Check the matching radio button
        const radio = document.querySelector(`input[name="accent"][value="${saved}"]`);
        if (radio) radio.checked = true;
    }
})();

function hexToRgb(hex) {
    const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return r ? { r: parseInt(r[1],16), g: parseInt(r[2],16), b: parseInt(r[3],16) } : null;
}

function shadeColor(hex, percent) {
    const rgb = hexToRgb(hex);
    if (!rgb) return hex;
    const clamp = v => Math.max(0, Math.min(255, v));
    const r = clamp(rgb.r + Math.round(255 * percent / 100));
    const g = clamp(rgb.g + Math.round(255 * percent / 100));
    const b = clamp(rgb.b + Math.round(255 * percent / 100));
    return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
}

// ════════════════════════════════════════════════════════════
//  PRODUCT MODAL
// ════════════════════════════════════════════════════════════
const modal       = document.getElementById('product-modal');
const productForm = document.getElementById('product-form');
const deleteBtn   = document.getElementById('delete-btn');
const saveBtn     = document.getElementById('save-btn');
let currentImageIndex = 0;
let existingImages    = [];

function openModal(mode, data = null) {
    modal.style.display = 'flex';

    // Full reset
    productForm.reset();
    document.querySelector('#variant-table tbody').innerHTML = '';
    clearAlbum();
    existingImages    = [];
    currentImageIndex = 0;
    document.getElementById('current-display-img').src = 'placeholder.jpg';

    if (mode === 'add') {
        document.getElementById('modal-title').innerText    = 'Add New Product';
        deleteBtn.style.display                             = 'none';
        delete productForm.dataset.productId;
        delete productForm.dataset.mode;
        document.getElementById('p-is-active').checked     = true;
        document.getElementById('p-is-new').checked        = false;
        document.getElementById('p-is-bestseller').checked = false;

    } else if (mode === 'edit' && data) {
        document.getElementById('modal-title').innerText    = 'Edit Product';
        deleteBtn.style.display                             = 'block';
        productForm.dataset.productId                       = data.id;
        productForm.dataset.mode                            = 'edit';

        document.getElementById('p-name').value            = data.name        || '';
        document.getElementById('p-price').value           = data.base_price  || '';
        document.getElementById('p-sale').value            = data.sale_price  || '';
        document.getElementById('p-desc').value            = data.description || '';
        document.getElementById('p-is-new').checked        = data.is_new        == 1;
        document.getElementById('p-is-bestseller').checked = data.is_bestseller == 1;
        document.getElementById('p-is-active').checked     = data.is_active     == 1;

        // Gender + classification from cat_slug (e.g. "women-top")
        const slug   = data.cat_slug || '';
        const gender = slug.startsWith('women') ? 'women' : 'men';
        const cls    = slug.replace('women-','').replace('men-','');
        document.getElementById('p-gender').value = gender;
        document.getElementById('p-class').value  = cls;

        loadImages(data.id);
        loadVariants(data.id);
    }
}

function closeModal() {
    modal.style.display = 'none';
    existingImages = [];
}
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// ── Images ────────────────────────────────────────────────────
function clearAlbum() {
    document.querySelectorAll('.album-item').forEach(el => el.remove());
}

function updateMainView(index) {
    const items   = document.querySelectorAll('.album-item img');
    const mainImg = document.getElementById('current-display-img');
    if (!items.length || !mainImg) return;
    if (index >= items.length) index = 0;
    if (index < 0) index = items.length - 1;
    mainImg.src       = items[index].src;
    currentImageIndex = index;
}

function changeSlide(dir) { updateMainView(currentImageIndex + dir); }

function renderThumbnail(src, dbImageId = null) {
    const album = document.getElementById('image-album');
    const slot  = document.querySelector('.add-image-slot-small');
    const div   = document.createElement('div');
    div.className = 'album-item';
    if (dbImageId) div.dataset.dbImageId = dbImageId;
    div.innerHTML = `
        <img src="${src}" alt=""
             onclick="updateMainView(
                 Array.from(this.parentElement.parentElement.children)
                      .indexOf(this.parentElement) - 1
             )">
        <button type="button" class="del-img-btn"
                onclick="removeImage(this.parentElement)">×</button>
    `;
    album.insertBefore(div, slot);
    updateMainView(document.querySelectorAll('.album-item').length - 1);
}

function removeImage(div) {
    const dbId = div.dataset.dbImageId;
    if (dbId) {
        const img = existingImages.find(i => String(i.id) === String(dbId));
        if (img) img.toDelete = true;
    }
    div.remove();
    updateMainView(0);
}

function loadImages(productId) {
    fetch('get_images.php?product_id=' + productId)
        .then(r => r.json())
        .then(images => {
            existingImages = images.map(img => ({ ...img, toDelete: false }));
            if (!images.length) {
                document.getElementById('current-display-img').src = 'placeholder.jpg';
                return;
            }
            images.forEach(img => renderThumbnail(img.image_url, img.id));
            updateMainView(0);
        })
        .catch(() => {
            document.getElementById('current-display-img').src = 'placeholder.jpg';
        });
}

document.getElementById('album-upload').addEventListener('change', function (e) {
    Array.from(e.target.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => renderThumbnail(ev.target.result);
        reader.readAsDataURL(file);
    });
});

// ── Variants ──────────────────────────────────────────────────
function loadVariants(productId) {
    fetch('get_variants.php?product_id=' + productId)
        .then(r => r.json())
        .then(variants => variants.forEach(v => renderVariantRow(v.size, v.color, v.color_hex, v.stock_qty)))
        .catch(() => {});
}

const colorPicker = document.getElementById('color-picker');
const colorText   = document.getElementById('var-color');
colorPicker.addEventListener('input', () => { colorText.value = colorPicker.value; });
colorText.addEventListener('input', () => {
    if (/^#[0-9A-Fa-f]{6}$/.test(colorText.value)) colorPicker.value = colorText.value;
});

// FIX: color name and hex are now fully independent — no sync between them
function addVariantRow() {
    const size      = document.getElementById('var-size').value;
    const colorName = document.getElementById('var-color').value.trim();
    const colorHex  = document.getElementById('color-picker').value; // always a valid #rrggbb
    const qty       = document.getElementById('var-qty').value.trim();
    if (!colorName) { alert('Please enter a color name (e.g. Navy, Black, Red).'); return; }
    if (qty === '')  { alert('Please enter a quantity.'); return; }
    renderVariantRow(size, colorName, colorHex, qty);
    document.getElementById('var-color').value    = '';
    document.getElementById('color-picker').value = '#000000';
    document.getElementById('var-qty').value      = '0';
}

// FIX: stores color name in data-color and hex in data-hex as separate attributes
function renderVariantRow(size, colorName, colorHex, qty) {
    const hex    = (colorHex && /^#[0-9A-Fa-f]{6}$/i.test(colorHex)) ? colorHex : null;
    const swatch = hex
        ? `<span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                        background:${hex};border:1px solid #ccc;margin-right:5px;
                        vertical-align:middle;"></span>`
        : '';
    const row = document.createElement('tr');
    // data-color = name only, data-hex = hex only — never mixed
    row.innerHTML = `
        <td><b>${esc(size)}</b></td>
        <td data-color="${esc(colorName)}" data-hex="${hex || ''}">${swatch}${esc(colorName)}</td>
        <td><input type="number" class="table-qty-input" value="${parseInt(qty)||0}" min="0"
                   style="width:70px;padding:4px;border:1px solid #ddd;border-radius:4px;"></td>
        <td><button type="button" class="remove-var-btn"
                onclick="this.closest('tr').remove()">&times;</button></td>
    `;
    document.querySelector('#variant-table tbody').appendChild(row);
}

// ── Form submit ───────────────────────────────────────────────
async function handleProductSubmit(event) {
    event.preventDefault();

    const mode      = productForm.dataset.mode      || 'add';
    const productId = productForm.dataset.productId || '';
    const fd        = new FormData();

    fd.append('action',         mode);
    fd.append('product_id',     productId);
    fd.append('name',           document.getElementById('p-name').value.trim());
    fd.append('gender',         document.getElementById('p-gender').value);
    fd.append('classification', document.getElementById('p-class').value);
    fd.append('price',          document.getElementById('p-price').value);
    fd.append('sale_price',     document.getElementById('p-sale').value);
    fd.append('description',    document.getElementById('p-desc').value.trim());
    fd.append('is_new',         document.getElementById('p-is-new').checked        ? 1 : 0);
    fd.append('is_bestseller',  document.getElementById('p-is-bestseller').checked ? 1 : 0);
    fd.append('is_active',      document.getElementById('p-is-active').checked     ? 1 : 0);

    fd.append('delete_image_ids', JSON.stringify(existingImages.filter(i => i.toDelete).map(i => i.id)));
    fd.append('keep_image_ids',   JSON.stringify(existingImages.filter(i => !i.toDelete).map(i => i.id)));

    const imageInput = document.getElementById('album-upload');
    for (let i = 0; i < imageInput.files.length; i++) {
        fd.append('product_images[]', imageInput.files[i]);
    }

    // FIX: read color name from data-color and hex from data-hex separately
    const variants = [];
    document.querySelectorAll('#variant-table tbody tr').forEach(row => {
        const size      = row.cells[0].innerText.trim();
        const colorName = row.cells[1].getAttribute('data-color') || '';
        const colorHex  = row.cells[1].getAttribute('data-hex')   || '';
        const qty       = row.querySelector('.table-qty-input').value;
        if (size && colorName) variants.push({ size, color: colorName, color_hex: colorHex, quantity: qty });
    });
    fd.append('variants', JSON.stringify(variants));

    saveBtn.disabled    = true;
    saveBtn.textContent = 'Saving…';

    try {
        const response = await fetch('process_add_product.php', { method: 'POST', body: fd });
        const result   = await response.text();
        const last     = result.trim().split('\n').pop().trim();
        console.log('Server:', result.trim());

        if (last === 'SUCCESS_PRODUCT_ADDED') {
            alert('✅ Product added!'); location.reload();
        } else if (last === 'SUCCESS_PRODUCT_UPDATED') {
            alert('✅ Product updated!'); location.reload();
        } else {
            alert('❌ Error:\n\n' + result);
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = 'Save to Collection';
    }
}
// ── Delete product ────────────────────────────────────────────
deleteBtn.addEventListener('click', async function () {
    const productId = productForm.dataset.productId;
    if (!productId) return;
    if (!confirm('⚠️ Permanently delete this product? This cannot be undone.')) return;

    const fd = new FormData();
    fd.append('action',     'delete');
    fd.append('product_id', productId);

    try {
        const response = await fetch('process_add_product.php', { method: 'POST', body: fd });
        const result   = await response.text();
        if (result.trim() === 'SUCCESS_PRODUCT_DELETED') {
            alert('🗑️ Product deleted.');
            closeModal(); location.reload();
        } else {
            alert('❌ Delete failed:\n\n' + result);
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    }
});

// ════════════════════════════════════════════════════════════
//  ADMIN PROFILE (settings card)
// ════════════════════════════════════════════════════════════
let currentReplyMsgId  = null;
let currentReplyEmail  = '';
let currentReplyName   = '';

// ── Avatar: preview + upload to server ───────────────────────
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Show preview immediately
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('settings-avatar-preview');
        if (prev) {
            prev.style.backgroundImage = `url(${e.target.result})`;
            prev.style.backgroundSize  = 'cover';
            prev.style.backgroundPosition = 'center';
            // Hide the user icon if present
            const icon = prev.querySelector('i');
            if (icon) icon.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);

    // Upload to server
    const fd = new FormData();
    fd.append('ajax_action', 'upload_avatar');
    fd.append('avatar', file);

    fetch('Manager.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update header profile pic too
                const headerPic = document.getElementById('header-profile-pic');
                if (headerPic) {
                    headerPic.style.backgroundImage    = `url(${data.url}?t=${Date.now()})`;
                    headerPic.style.backgroundSize     = 'cover';
                    headerPic.style.backgroundPosition = 'center';
                    headerPic.style.background         = 'none'; // remove gradient
                    headerPic.style.backgroundImage    = `url(${data.url}?t=${Date.now()})`;
                }
                showAvatarMsg('Profile picture saved!', true);
            } else {
                showAvatarMsg(data.message || 'Upload failed.', false);
            }
        })
        .catch(err => showAvatarMsg('Network error: ' + err.message, false));
}

function removeProfilePic() {
    if (!confirm('Remove your profile picture?')) return;
    const fd = new FormData();
    fd.append('ajax_action', 'remove_avatar');

    fetch('Manager.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const prev = document.getElementById('settings-avatar-preview');
                if (prev) {
                    prev.style.backgroundImage = '';
                    // Restore icon
                    if (!prev.querySelector('i')) {
                        prev.innerHTML = '<i class="fas fa-user" style="font-size:28px;color:#aaa;"></i>';
                    } else {
                        prev.querySelector('i').style.display = '';
                    }
                }
                // Reset header pic to gradient
                const headerPic = document.getElementById('header-profile-pic');
                if (headerPic) {
                    headerPic.style.backgroundImage = '';
                    headerPic.style.background = 'linear-gradient(45deg, #2c3e50, #3498db)';
                }
                showAvatarMsg('Profile picture removed.', true);
            }
        })
        .catch(err => showAvatarMsg('Error: ' + err.message, false));
}

function showAvatarMsg(msg, success) {
    // Show a brief toast near the avatar area
    let toast = document.getElementById('avatar-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'avatar-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;'
            + 'font-size:13px;font-weight:600;z-index:99999;transition:opacity .3s;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.background = success ? '#27ae60' : '#e74c3c';
    toast.style.color      = '#fff';
    toast.style.opacity    = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 3000);
}

// ── Reply modal ───────────────────────────────────────────────
function openReplyModal(msgId, name, email, originalMsg) {
    currentReplyMsgId = msgId;
    currentReplyEmail = email;
    currentReplyName  = name;

    document.getElementById('reply-to-name').textContent    = name;
    document.getElementById('reply-to-email').textContent   = email;
    document.getElementById('reply-original-msg').textContent = originalMsg;
    document.getElementById('reply-text-input').value       = '';
    document.getElementById('reply-modal-error').style.display   = 'none';
    document.getElementById('reply-modal-success').style.display = 'none';

    document.getElementById('reply-modal-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('reply-text-input').focus(), 100);
}

function closeReplyModal() {
    document.getElementById('reply-modal-overlay').style.display = 'none';
    currentReplyMsgId = null;
}

document.getElementById('reply-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeReplyModal();
});

async function submitReply() {
    const replyText = document.getElementById('reply-text-input').value.trim();
    const errEl     = document.getElementById('reply-modal-error');
    const succEl    = document.getElementById('reply-modal-success');
    const btn       = document.getElementById('reply-send-btn');

    errEl.style.display  = 'none';
    succEl.style.display = 'none';

    if (!replyText) {
        errEl.textContent    = 'Please write a reply before sending.';
        errEl.style.display  = 'block';
        return;
    }

    btn.disabled    = true;
    btn.innerHTML   = '<i class="fas fa-spinner fa-spin"></i> Sending…';

    const fd = new FormData();
    fd.append('ajax_action', 'send_reply');
    fd.append('msg_id',      currentReplyMsgId);
    fd.append('to_email',    currentReplyEmail);
    fd.append('to_name',     currentReplyName);
    fd.append('reply_text',  replyText);

    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();

        if (data.success) {
            succEl.textContent   = 'Reply sent successfully!';
            succEl.style.display = 'block';
            // Mark card as read in the UI
            if (currentReplyMsgId) {
                const card = document.getElementById('msg-card-' + currentReplyMsgId);
                if (card) {
                    card.classList.remove('msg-unread');
                    card.classList.add('msg-read');
                    const dot = card.querySelector('.msg-new-dot');
                    if (dot) dot.remove();
                    const readBtn = card.querySelector('.msg-btn-read');
                    if (readBtn) readBtn.outerHTML = '<span class="msg-read-badge"><i class="fas fa-check-double"></i> Read</span>';
                }
                updateMsgBadge(-1);
            }
            setTimeout(closeReplyModal, 1800);
        } else {
            errEl.textContent   = data.message || 'Failed to send reply.';
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent   = 'Network error: ' + err.message;
        errEl.style.display = 'block';
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
    }
}



// ── Email update modal ───────────────────────────────────────
function openEmailModal() {
    clearModalFeedback('email');
    document.getElementById('new-email-input').value      = '';
    document.getElementById('email-confirm-password').value = '';
    const overlay = document.getElementById('email-modal-overlay');
    overlay.style.display = 'flex';
}
function closeEmailModal() {
    document.getElementById('email-modal-overlay').style.display = 'none';
}
document.getElementById('email-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeEmailModal();
});

async function submitEmailUpdate() {
    clearModalFeedback('email');
    const newEmail = document.getElementById('new-email-input').value.trim();
    const password = document.getElementById('email-confirm-password').value;

    if (!newEmail) { showModalError('email', 'Please enter a new email address.'); return; }
    if (!password) { showModalError('email', 'Please enter your current password.'); return; }

    const fd = new FormData();
    fd.append('ajax_action', 'update_admin_email');
    fd.append('new_email',   newEmail);
    fd.append('password',    password);

    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showModalSuccess('email', 'Email updated successfully!');
            document.getElementById('current-email-display').textContent = data.new_email;
            document.getElementById('new-email-input').value = '';
            document.getElementById('email-confirm-password').value = '';
        } else {
            showModalError('email', data.message || 'Update failed.');
        }
    } catch (err) {
        showModalError('email', 'Network error: ' + err.message);
    }
}

// ── Password update modal ─────────────────────────────────────
function openPassModal() {
    clearModalFeedback('pass');
    document.getElementById('current-pass-input').value = '';
    document.getElementById('new-pass-input').value     = '';
    document.getElementById('confirm-pass-input').value = '';
    const overlay = document.getElementById('pass-modal-overlay');
    overlay.style.display = 'flex';
}
function closePassModal() {
    document.getElementById('pass-modal-overlay').style.display = 'none';
}
document.getElementById('pass-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closePassModal();
});

async function submitPassUpdate() {
    clearModalFeedback('pass');
    const current = document.getElementById('current-pass-input').value;
    const newPw   = document.getElementById('new-pass-input').value;
    const confirm = document.getElementById('confirm-pass-input').value;

    if (!current) { showModalError('pass', 'Please enter your current password.'); return; }
    if (newPw.length < 6) { showModalError('pass', 'New password must be at least 6 characters.'); return; }
    if (newPw !== confirm) { showModalError('pass', 'New passwords do not match.'); return; }

    const fd = new FormData();
    fd.append('ajax_action',      'update_admin_password');
    fd.append('current_password', current);
    fd.append('new_password',     newPw);
    fd.append('confirm_password', confirm);

    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showModalSuccess('pass', 'Password updated successfully!');
            document.getElementById('current-pass-input').value = '';
            document.getElementById('new-pass-input').value     = '';
            document.getElementById('confirm-pass-input').value = '';
        } else {
            showModalError('pass', data.message || 'Update failed.');
        }
    } catch (err) {
        showModalError('pass', 'Network error: ' + err.message);
    }
}

// ── Modal feedback helpers ────────────────────────────────────
function showModalError(prefix, msg) {
    const el = document.getElementById(prefix + '-modal-error');
    if (el) { el.textContent = msg; el.style.display = 'block'; }
}
function showModalSuccess(prefix, msg) {
    const el = document.getElementById(prefix + '-modal-success');
    if (el) { el.textContent = msg; el.style.display = 'block'; }
}
function clearModalFeedback(prefix) {
    ['error','success'].forEach(t => {
        const el = document.getElementById(prefix + '-modal-' + t);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
    });
}

// ── Messages — mark as read ───────────────────────────────────
async function markRead(msgId, btn) {
    const fd = new FormData();
    fd.append('ajax_action', 'mark_message_read');
    fd.append('msg_id', msgId);
    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            const card = document.getElementById('msg-card-' + msgId);
            if (card) {
                card.classList.remove('msg-unread');
                card.classList.add('msg-read');
                // Replace button with "Read" badge
                btn.outerHTML = '<span class="msg-read-badge"><i class="fas fa-check-double"></i> Read</span>';
                // Remove the blue dot from the name
                const dot = card.querySelector('.msg-new-dot');
                if (dot) dot.remove();
            }
            // Update sidebar badge count
            updateMsgBadge(-1);
        }
    } catch (err) { alert('Error: ' + err.message); }
}

// ── Messages — delete ─────────────────────────────────────────
async function deleteMessage(msgId, btn) {
    if (!confirm('Delete this message? This cannot be undone.')) return;
    const card = document.getElementById('msg-card-' + msgId);
    const wasUnread = card && card.classList.contains('msg-unread');

    const fd = new FormData();
    fd.append('ajax_action', 'delete_message');
    fd.append('msg_id', msgId);
    try {
        const r    = await fetch('Manager.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            if (card) {
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(.95)';
                setTimeout(() => card.remove(), 300);
            }
            if (wasUnread) updateMsgBadge(-1);
        }
    } catch (err) { alert('Error: ' + err.message); }
}

// ── Update sidebar unread badge ───────────────────────────────
function updateMsgBadge(delta) {
    const badge = document.getElementById('msg-badge');
    if (!badge) return;
    let count = parseInt(badge.textContent) + delta;
    if (count <= 0) { badge.remove(); }
    else { badge.textContent = count; }
}

// ── Messages search ───────────────────────────────────────────
const msgSearch = document.getElementById('msg-search');
if (msgSearch) {
    msgSearch.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase().trim();
        document.querySelectorAll('.msg-card').forEach(card => {
            const text = (card.dataset.name + ' ' + card.dataset.email + ' ' + card.dataset.message);
            card.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// ════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════
function cap(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''; }
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Function to generate a lighter shade
function getLighterShade(hex, percent) {
    // Convert hex to RGB
    let r = parseInt(hex.slice(1, 3), 16);
    let g = parseInt(hex.slice(3, 5), 16);
    let b = parseInt(hex.slice(5, 7), 16);

    // Lighten each channel
    r = Math.floor(r + (255 - r) * (percent / 100));
    g = Math.floor(g + (255 - g) * (percent / 100));
    b = Math.floor(b + (255 - b) * (percent / 100));

    // Convert back to hex
    const toHex = (c) => c.toString(16).padStart(2, '0');
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
}

// ════════════════════════════════════════════════════════════
//  CHARTS — initialised after DOM ready
// ════════════════════════════════════════════════════════════
function initCharts() {
    const d = window.VELVET_CHARTS;
    if (!d || typeof Chart === 'undefined') return;

    // Resolve accent colour from CSS variable (set by setAccent / localStorage)
    const accent = getComputedStyle(document.documentElement)
        .getPropertyValue('--accent').trim() || '#3498db';

    // Helper: hex -> rgba
    function hexRgba(hex, alpha) {
        const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (!r) return `rgba(52,152,219,${alpha})`;
        return `rgba(${parseInt(r[1],16)},${parseInt(r[2],16)},${parseInt(r[3],16)},${alpha})`;
    }

    // Shared font defaults
    Chart.defaults.font.family = "'Inter','Segoe UI',sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#888';

    const isDark = document.body.classList.contains('dark-mode');
    const gridColor  = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
    const labelColor = isDark ? '#aaa' : '#666';


    // Palette for multi-colour charts
    const PALETTE = [accent];
    for (let i = 1; i <= 8; i++) {
        PALETTE.push(getLighterShade(accent, i * 12));
    }

    // ── 1. Monthly Revenue — gradient line chart ──────────────
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        const grad = revCtx.getContext('2d').createLinearGradient(0, 0, 0, 220);
        grad.addColorStop(0,   hexRgba(accent, 0.35));
        grad.addColorStop(1,   hexRgba(accent, 0.02));

        new Chart(revCtx, {
            type: 'line',
            data: {
                labels:   d.revenue.labels,
                datasets: [{
                    label:           'Revenue (₪)',
                    data:            d.revenue.data,
                    borderColor:     accent,
                    backgroundColor: grad,
                    borderWidth:     2.5,
                    pointBackgroundColor: accent,
                    pointRadius:     5,
                    pointHoverRadius:7,
                    tension:         0.4,
                    fill:            true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ₪' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: labelColor } },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: labelColor,
                            callback: v => '₪' + v.toLocaleString()
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // ── 2. Daily Orders — bar chart ───────────────────────────
    const dayCtx = document.getElementById('dailyChart');
    if (dayCtx) {
        const barColors = d.daily.data.map((v, i) => {
            const max = Math.max(...d.daily.data);
            return v === max ? accent : hexRgba(accent, 0.45);
        });

        new Chart(dayCtx, {
            type: 'bar',
            data: {
                labels:   d.daily.labels,
                datasets: [{
                    label:           'Orders',
                    data:            d.daily.data,
                    backgroundColor: barColors,
                    borderRadius:    6,
                    borderSkipped:   false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: labelColor } },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: labelColor, stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // ── 3. Order Status — doughnut ────────────────────────────
    const statCtx = document.getElementById('statusChart');
    if (statCtx) {
        const statusColors = ['#f39c12','#66ccff','#e67e22','#59d98e','#27ae60','#b30000'];
        new Chart(statCtx, {
            type: 'doughnut',
            data: {
                labels:   d.status.labels,
                datasets: [{
                    data:            d.status.data,
                    backgroundColor: statusColors,
                    borderColor:     isDark ? '#1e1e1e' : '#fff',
                    borderWidth:     3,
                    hoverOffset:     8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color:     labelColor,
                            padding:   12,
                            boxWidth:  12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} orders`
                        }
                    }
                }
            }
        });
    }

    // ── 4. Top Products — horizontal bar ─────────────────────
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx) {
        const truncate = (str, n) => str.length > n ? str.slice(0, n) + '…' : str;
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels:   d.topProducts.labels.map(l => truncate(l, 22)),
                datasets: [{
                    label:           'Units Sold',
                    data:            d.topProducts.data,
                    backgroundColor: PALETTE.slice(0, d.topProducts.data.length),
                    borderRadius:    5,
                    borderSkipped:   false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: labelColor, stepSize: 1 },
                        beginAtZero: true
                    },
                    y: { grid: { display: false }, ticks: { color: labelColor } }
                }
            }
        });
    }

    // ── 5. Revenue by Category — doughnut ────────────────────
    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        const catColors = ['#33adff','#ff80d5','#66ccff','#27ae60','#f39c12','#9b59b6'];
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels:   d.category.labels,
                datasets: [{
                    data:            d.category.data,
                    backgroundColor: catColors.slice(0, d.category.data.length),
                    borderColor:     isDark ? '#1e1e1e' : '#fff',
                    borderWidth:     3,
                    hoverOffset:     8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color:   labelColor,
                            padding: 12,
                            boxWidth:12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ₪${ctx.parsed.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }
}

// Run after DOM ready
document.addEventListener('DOMContentLoaded', initCharts);
