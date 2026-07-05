function gotologinpage(){
    window.location.href="login.php";
}
function gototheforgotpage(){
    window.location.href="Forgot Password.php";
}
function gotothesignuppage(){
    window.location.href="signup.php";
}
function gotoshoppage(){
    window.location.href="shop.php";
}
function gotocheckoutpage(){
    window.location.href="Checkout.php";
}
document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('mainLoginBtn');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    const hideLoginIfSmall = () => {
        if (window.innerWidth <= 756 && loginBtn) {
            loginBtn.style.display = 'none';
        }
    };

    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (!this.hasAttribute('data-bs-toggle')) {
                hideLoginIfSmall();
            }
        });
    });


    document.addEventListener('show.bs.modal', function () {
        hideLoginIfSmall();
    });

    document.addEventListener('hidden.bs.modal', function () {
        if (loginBtn) loginBtn.style.display = 'block';
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 756 && loginBtn) {
            loginBtn.style.display = 'block';
        }
    });
});

let countdown;

function sendCode() {
    const emailInput = document.getElementById('userEmail');
    const emailError = document.getElementById('emailError');
    const emailValue = emailInput.value.trim();

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (emailPattern.test(emailValue)) {
        emailError.style.display = 'none';
        emailInput.classList.remove('is-invalid');

        console.log("Valid Email. Sending code to: " + emailValue);

        showStep(2);
        startTimer(120);
    } else {
        emailError.style.display = 'block';
        emailInput.classList.add('is-invalid');
    }
}

function verifyCode() {
    const otpInput = document.getElementById('otpCode');
    const otpError = document.getElementById('otpError');

    if (otpInput.value.length === 6) {
        otpError.style.display = 'none';
        showStep(3);
        clearInterval(countdown);
    } else {
        otpError.style.display = 'block';
        otpInput.classList.add('is-invalid');
    }
}

function resetPassword() {
    const p1 = document.getElementById('newPass').value;
    const p2 = document.getElementById('confirmPass').value;
    const passError = document.getElementById('passError');

    if (p1.length >= 6 && p1 === p2) {
        passError.style.display = 'none';
        alert("Success! Your password has been updated.");
        window.location.href = "login.php";
    } else {
        passError.style.display = 'block';
        if(p1 !== p2) passError.innerText = "Passwords do not match!";
        else passError.innerText = "Password must be at least 6 characters.";
    }
}

function showStep(stepNumber) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + stepNumber).classList.add('active');
}

function startTimer(duration) {
    let timer = duration, minutes, seconds;
    const display = document.getElementById('timer');

    countdown = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        seconds = seconds < 10 ? "0" + seconds : seconds;
        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(countdown);
            alert("Time expired! Please request a new code.");
            showStep(1);
        }
    }, 1000);
}