const togglePasswordOwner = document.getElementById('togglePasswordOwner');
const passwordFieldOwner = document.getElementById('password-owner');

togglePasswordOwner.addEventListener('click', function () {
    const type = passwordFieldOwner.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordFieldOwner.setAttribute('type', type);
    this.classList.toggle('bi-eye');
    this.classList.toggle('bi-eye-slash');
});
