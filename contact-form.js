(() => {
  const form = document.querySelector('.contact-form[action="send-email.php"]');
  if (!form) return;
  const started = form.querySelector('input[name="form_started"]');
  if (started) started.value = String(Date.now());
  const result = new URLSearchParams(window.location.search).get('form');
  const status = form.querySelector('.form-status');
  if (!result || !status) return;
  status.hidden = false;
  status.classList.add('error');
  const messages = {
    config: 'Email sending is not configured on the server yet. Please email info@srexsolutions.com directly.',
    validation: 'Please check your name and email address, then try again.',
    rate: 'Please wait 30 seconds before sending another enquiry.',
    smtp: 'The mail server did not accept the enquiry. Please try again or email info@srexsolutions.com directly.'
  };
  status.textContent = messages[result] || 'Your enquiry could not be sent. Please try again or email info@srexsolutions.com.';
  status.scrollIntoView({ behavior: 'smooth', block: 'center' });
})();
