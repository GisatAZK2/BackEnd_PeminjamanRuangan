  function togglePassword(btn) {
      const input = btn.parentElement.querySelector('input');
      const show = btn.querySelector('.show-pass');
      const hide = btn.querySelector('.hide-pass');
      if (input.type === 'password') {
        input.type = 'text';
        show.classList.remove('hidden');
        hide.classList.add('hidden');
      } else {
        input.type = 'password';
        show.classList.add('hidden');
        hide.classList.remove('hidden');
      }
    }

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      const btn = document.getElementById('submitBtn');
      const errorAlert = document.getElementById('error-alert');
      const errorMsg = document.getElementById('error-message');

      btn.disabled = true;
      btn.textContent = 'Signing In...';

      const formData = new FormData();
      formData.append('username', username);
      formData.append('password', password);

      try {
        const res = await fetch('/api/login', {
          method: 'POST',
          body: formData
        });

        const data = await res.json();

        if (data.status === 'success') {
          window.location.href = '/dashboard';
        } else {
          errorMsg.textContent = data.message;
          errorAlert.classList.remove('hidden');
        }
      } catch (err) {
        errorMsg.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
        errorAlert.classList.remove('hidden');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Sign In';
      }
    });