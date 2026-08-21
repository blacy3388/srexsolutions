(function () {
  "use strict";

  const config = window.SREX_GOOGLE_CONFIG || {};
  const consentKey = "srex_analytics_consent";
  let analyticsLoaded = false;

  function validGaId() {
    return config.analyticsEnabled &&
      typeof config.ga4MeasurementId === "string" &&
      /^G-[A-Z0-9]+$/i.test(config.ga4MeasurementId) &&
      config.ga4MeasurementId !== "G-XXXXXXXXXX";
  }

  function loadAnalytics() {
    if (analyticsLoaded || !validGaId()) return;
    analyticsLoaded = true;

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag("js", new Date());
    window.gtag("config", config.ga4MeasurementId, {
      anonymize_ip: true,
      allow_google_signals: false
    });

    const script = document.createElement("script");
    script.async = true;
    script.src = "https://www.googletagmanager.com/gtag/js?id=" +
      encodeURIComponent(config.ga4MeasurementId);
    document.head.appendChild(script);
  }

  function setConsent(value) {
    localStorage.setItem(consentKey, value);
    document.getElementById("srex-cookie-banner")?.remove();
    if (value === "granted") loadAnalytics();
  }

  function showBanner() {
    if (document.getElementById("srex-cookie-banner")) return;
    const banner = document.createElement("aside");
    banner.id = "srex-cookie-banner";
    banner.className = "cookie-banner";
    banner.setAttribute("aria-label", "Cookie preferences");
    banner.innerHTML = `
      <div class="cookie-banner__content">
        <div>
          <strong>Optional analytics cookies</strong>
          <p>We use Google Analytics only after you accept, to understand website use and improve our services. <a href="cookie-policy.html">Read the cookie policy</a>.</p>
        </div>
        <div class="cookie-banner__actions">
          <button type="button" class="button button-outline" data-cookie-choice="denied">Reject</button>
          <button type="button" class="button button-primary" data-cookie-choice="granted">Accept analytics</button>
        </div>
      </div>`;
    document.body.appendChild(banner);
    banner.querySelectorAll("[data-cookie-choice]").forEach(button => {
      button.addEventListener("click", () => setConsent(button.dataset.cookieChoice));
    });
  }

  function trackClick(event) {
    if (!analyticsLoaded || typeof window.gtag !== "function") return;
    const link = event.target.closest("a, button");
    if (!link) return;

    const href = link.getAttribute("href") || "";
    let eventName = "";

    if (href.startsWith("tel:")) eventName = "phone_click";
    else if (href.startsWith("mailto:")) eventName = "email_click";
    else if (href.includes("contact.html") || link.closest(".contact-form")) eventName = "contact_intent";
    else if (link.classList.contains("button")) eventName = "cta_click";

    if (eventName) {
      window.gtag("event", eventName, {
        link_url: href || undefined,
        link_text: (link.textContent || "").trim().slice(0, 100),
        page_location: window.location.href
      });
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const storedConsent = localStorage.getItem(consentKey);
    if (storedConsent === "granted") loadAnalytics();
    else if (storedConsent !== "denied") showBanner();

    document.addEventListener("click", trackClick);

    document.querySelectorAll("[data-open-cookie-settings]").forEach(link => {
      link.addEventListener("click", event => {
        event.preventDefault();
        localStorage.removeItem(consentKey);
        showBanner();
      });
    });
  });
})();
