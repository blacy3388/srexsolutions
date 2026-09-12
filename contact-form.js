(() => {
  const form = document.querySelector('.contact-form[action="send-email.php"]');
  if (!form) return;
  const started = form.querySelector('input[name="form_started"]');
  if (started) started.value = String(Date.now());
  const result = new URLSearchParams(window.location.search).get('form');
  const status = form.querySelector('.form-status');
  if (!result || !status) return;
  status.hidden = false;
  if (result === 'sent') {
    status.classList.add('success');
    status.textContent = 'Thank you. Your enquiry has been sent successfully.';
    form.reset();
  } else {
    status.classList.add('error');
    status.textContent = 'Your enquiry could not be sent. Please try again or email info@srexsolutions.com.';
  }
  status.scrollIntoView({ behavior: 'smooth', block: 'center' });
})();
