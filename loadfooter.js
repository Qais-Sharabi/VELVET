function loadFooter(settings) {
    const footerHTML = `
    <footer class="bg-dark text-white">
    <div class="container">
        <div class="row text-center text-md-start justify-content-center">
            <div class="col-md-5 col-lg-4 mt-3">
                <h5 class="text-uppercase mb-3 font-weight-bold text-white text-center ">${settings.store_name}</h5>
                <p class="text-center">Bringing you the latest fashion trends with the best quality. Elevate your wardrobe with our exclusive collections.</p>
            </div>

            <div class="col-md-5 col-lg-4 mt-3">
                <h5 class="text-uppercase mb-3 font-weight-bold text-white text-center ">Contact</h5>
                <div class="d-flex flex-column align-items-center">
                    <p><i class="fas fa-home mr-3"></i> Online Store</p>
                    <p><i class="fas fa-envelope mr-3"></i> ${settings.support_email}</p>
                    <p><i class="fas fa-phone mr-3"></i> ${settings.support_phone}</p>
                </div>
            </div>
        </div>

        <hr class="mb-2 mt-1" >

        <div class="row">
            <div class="col-12 text-center">
                <p>Copyright © 2026 All rights reserved by:
                    <a href="#" style="text-decoration: none;">
                        <strong class="text-white">${settings.store_name}</strong>
                    </a>
                </p>
                <ul class="list-unstyled list-inline mt-3">
                    <li class="list-inline-item">
                        <a href="${settings.facebook_url}" target="_blank" class="text-white mx-2" style="font-size: 30px;"><i class="fab fa-facebook"></i></a>
                    </li>
                    <li class="list-inline-item">
                        <a href="${settings.instagram_url}" target="_blank" class="text-white mx-2" style="font-size: 30px;"><i class="fab fa-instagram"></i></a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://wa.me/${settings.support_phone.replace(/\D/g,'')}" target="_blank" class="text-white mx-2" style="font-size: 30px;">
                        <i class="fab fa-whatsapp"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>`;

    // Important: Remove the existing footer if it's already there to prevent duplicates
    const oldFooter = document.querySelector('footer');
    if (oldFooter) oldFooter.remove();

    document.body.insertAdjacentHTML('beforeend', footerHTML);
}