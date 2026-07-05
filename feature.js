const stylistColors = [
    { name: 'Black',    hex: '#1a1a1a' },
    { name: 'Brown',    hex: '#4d2600' },
    { name: 'White',    hex: '#FFFFFF' },
    { name: 'Beige',    hex: '#dbae8a' },
    { name: 'Cream',    hex: '#ebd9c7' },
    { name: 'Navy',     hex: '#000080' },
    { name: 'Baby Blue',hex: '#99ddff' },
    { name: 'Maroon',   hex: '#800000' },
    { name: 'Red',      hex: '#e60000' },
    { name: 'Orange',   hex: '#ff8c1a' },
    { name: 'Yellow',   hex: '#ffff4d' },
    { name: 'Green',    hex: '#006600' },
    { name: 'Grey',     hex: '#808080' },
    { name: 'Hot Pink', hex: '#e60073' },
    { name: 'Pink',     hex: '#ffb3da' },
    { name: 'Purple',   hex: '#660066' },
];

let userPalette = [];
let outfitMode  = 0;
let totalSlots  = null;
let excludeMap  = {};

// ─── Init color grid ──────────────────────────────────────────────────────────

function initStylist() {
    const grid = document.getElementById('stylist-color-grid');
    if (!grid) return;
    grid.innerHTML = '';
    stylistColors.forEach(color => {
        const div                 = document.createElement('div');
        div.className             = 'color-circle';
        div.style.backgroundColor = color.hex;
        div.title                 = color.name;
        if (color.hex === '#FFFFFF') div.style.border = '1px solid #ccc';
        div.onclick = () => handleColorClick(color.hex, div);
        grid.appendChild(div);
    });
}

// ─── Color selection ──────────────────────────────────────────────────────────

function handleColorClick(colorHex, element) {
    const index = userPalette.indexOf(colorHex);
    if (index > -1) {
        userPalette.splice(index, 1);
        element.classList.remove('active');
    } else {
        if (userPalette.length < 3) {
            userPalette.push(colorHex);
            element.classList.add('active');
        } else {
            alert('You can select up to 3 colors.');
        }
    }
    updateStylistUI();
}

function updateStylistUI() {
    const btn               = document.getElementById('build-outfit-btn');
    const selectedContainer = document.getElementById('selected-colors-row');

    if (selectedContainer) {
        selectedContainer.innerHTML = '';
        userPalette.forEach(hex => {
            const dot                 = document.createElement('div');
            dot.className             = 'color-circle';
            dot.style.backgroundColor = hex;
            if (hex === '#FFFFFF') dot.style.border = '1px solid #ccc';
            selectedContainer.appendChild(dot);
        });
    }

    if (btn) btn.disabled = userPalette.length === 0;
}

// ─── Modal toggle ─────────────────────────────────────────────────────────────

function toggleStylistModal() {
    const modal = document.getElementById('stylist-modal');
    if (!modal) return;
    const isHidden = window.getComputedStyle(modal).display === 'none';
    modal.style.display = isHidden ? 'flex' : 'none';
}

// ─── Skeleton loader ──────────────────────────────────────────────────────────

function showSkeleton(container, count = 2) {
    const skeleton     = document.createElement('div');
    skeleton.className = 'skeleton-card';
    skeleton.id        = 'outfit-skeleton';

    const row = document.createElement('div');
    row.className = 'skeleton-row';

    for (let i = 0; i < count; i++) {
        row.innerHTML += `
            <div class="skeleton-item">
                <div class="skeleton-img"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
            </div>
        `;
    }

    skeleton.appendChild(row);
    container.appendChild(skeleton);
}

function removeSkeleton() {
    const skeleton = document.getElementById('outfit-skeleton');
    if (skeleton) skeleton.remove();
}

// ─── Image lazy-load with fade-in ─────────────────────────────────────────────

function lazyLoadImages(card) {
    const imgDivs = card.querySelectorAll('.chosen-img[data-bg]');
    imgDivs.forEach(div => {
        const url = div.getAttribute('data-bg');
        const img = new Image();
        img.onload = () => {
            div.style.backgroundImage = `url('${url}')`;
            div.classList.add('loaded');
        };
        img.onerror = () => {
            div.style.backgroundImage = `url('images/general/placeholder.jpg')`;
            div.classList.add('loaded');
        };
        img.src = url;
    });
}

// ─── Fetch & render outfits ───────────────────────────────────────────────────

async function generateOutfits(appendMode = false) {
    const btn         = document.getElementById('build-outfit-btn');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const container   = document.getElementById('outfit-display-container');
    const gender      = document.querySelector('input[name="gender"]:checked').value;

    // Always clear previous outfit
    container.innerHTML = '';

    if (!appendMode) {
        outfitMode = 0;
        totalSlots = null;
        excludeMap = {};
    }

    // Show skeleton (2 items for two-piece, 1 for unknown)
    showSkeleton(container, 2);

    if (btn)         btn.disabled         = true;
    if (loadMoreBtn) loadMoreBtn.disabled = true;

    const formData = new FormData();
    formData.append('gender',     gender);
    formData.append('colors',     JSON.stringify(userPalette));
    formData.append('mode',       outfitMode);
    formData.append('excludeMap', JSON.stringify(excludeMap));

    try {
        const response = await fetch('get_outfits.php', { method: 'POST', body: formData });
        const data     = await response.json();
        console.log('Products received:', data.products);
        removeSkeleton();

        if (data.totalSlots !== undefined) totalSlots = data.totalSlots;

        if (!data.products || data.products.length === 0) {
            const msg       = document.createElement('p');
            msg.className   = 'no-results-msg';
            msg.textContent = appendMode
                ? 'No more outfit combinations available!'
                : 'No matching outfits found. Try different colors!';
            container.appendChild(msg);
            if (loadMoreBtn) loadMoreBtn.disabled = true;
            return;
        }

        // Track shown products per slot
        const slotKey = data.slotKey;
        if (slotKey) {
            if (!excludeMap[slotKey]) excludeMap[slotKey] = [];
            data.products.forEach(p => {
                if (!excludeMap[slotKey].includes(p.id)) {
                    excludeMap[slotKey].push(p.id);
                }
            });
        }

        outfitMode++;

        // Build outfit card
        const outfitCard     = document.createElement('div');
        outfitCard.className = 'outfit-group-card';
        const typeLabel      = data.type === 'one-piece' ? 'One Piece' : 'Outfit';
        outfitCard.innerHTML = `
            <div class="outfit-items-row">
                ${data.products.map(product => `
                    <div class="outfit-item-card">
                        <a href="product_details.php?slug=${product.slug}" target="_blank" style="text-decoration:none; color:black;">
                            <div class="chosen-img" data-bg="${product.image_url || 'images/general/placeholder.jpg'}"></div>
                            <div class="outfit-item-info" style="text-align: center;">
                                <small class="item-name">${product.name}</small>
                            </div>
                        </a>
                    </div>
                `).join('')}
            </div>
        `;

        container.appendChild(outfitCard);

        // Trigger image lazy load with fade-in after card is in DOM
        lazyLoadImages(outfitCard);

        if (loadMoreBtn) {
            loadMoreBtn.disabled = (totalSlots !== null && outfitMode >= totalSlots);
        }

    } catch (error) {
        removeSkeleton();
        console.error('Outfit builder error:', error);
        const errEl       = document.createElement('p');
        errEl.className   = 'no-results-msg';
        errEl.textContent = 'Something went wrong. Please try again.';
        container.appendChild(errEl);

    } finally {
        if (btn) btn.disabled = false;
    }
}

function buildOutfits() {
    outfitMode = 0;
    totalSlots = null;
    excludeMap = {};
    generateOutfits(false);
}

function addNewOutfit() {
    generateOutfits(true);
}

document.addEventListener('DOMContentLoaded', initStylist);


const faqResponses = {
    'Shipping': 'Our standard shipping takes 3-5 business days. Shipping is a flat rate of 20 ₪, but you get ' +
        'Free Shipping on all orders over 300 ₪! You will receive a notification as soon as your package leaves our warehouse.',
    'Payment': 'Answer: We currently support Cash on Delivery(COD) and all major credit cards. Your transaction is 100% secure through our encrypted checkout.',
    'Size': 'We recommend checking the Size Guide on the product page. Most of our items have a relaxed, true-to-size fit.',
    'Return': 'You can return any item within 14 days of delivery. Make sure tags are still attached and the item is unworn.',
    'Contact': 'Email us at support@store.ps or WhatsApp +970 59-XXX-XXXX.',
    'Privacy': 'We only use your data to ship orders and keep your account secure. No third-party sharing!'
};

function toggleFAQModal() {
    const modal = document.getElementById('faq-modal');
    const isHidden = modal.style.display === 'none' || modal.style.display === '';
    modal.style.display = isHidden ? 'flex' : 'none';
}

function handleChatSelection(key, questionText) {
    const chatBody = document.getElementById('chatBody');
    const options = document.getElementById('chatOptions');

    const userMsg = document.createElement('div');
    userMsg.className = 'message user-msg';
    userMsg.innerText = questionText;
    chatBody.appendChild(userMsg);

    options.style.opacity = '0';
    options.style.pointerEvents = 'none';
    chatBody.scrollTop = chatBody.scrollHeight;

    setTimeout(() => {
        const botMsg = document.createElement('div');
        botMsg.className = 'message bot-msg';
        botMsg.innerText = faqResponses[key];
        chatBody.appendChild(botMsg);

        options.style.opacity = '1';
        options.style.pointerEvents = 'auto';
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 1000);
}
