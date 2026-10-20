/**
 * MIB PHOTOMAN - Master JavaScript
 * Handles package photo sliders, "Book Now!" card clicks, live customizer pricing,
 * auth tab switching, and simulated email booking confirmations.
 */

initPageLoader();

document.addEventListener('DOMContentLoaded', () => {
  initSliders();
  initAuth();
  initBookingCalculator();
  initNavigationUser();
  initMobileNav();
  initHeaderScroll();
  initScrollReveal();
  initHeroVideo();
  initBackToTop();
});

/* ==========================================================
   0. PAGE LOADER (CAMERA VIEWFINDER)
   ========================================================== */
function initPageLoader() {
  const loader = document.getElementById('page-loader');
  if (!loader) return;

  const bar = document.getElementById('loader-bar');
  const count = document.getElementById('loader-count');

  // Full intro on the first page of a visit, quick blink on later pages
  let firstVisit = true;
  try {
    firstVisit = !sessionStorage.getItem('mib_loader_seen');
    sessionStorage.setItem('mib_loader_seen', '1');
  } catch (e) { /* storage unavailable — treat as first visit */ }

  const minDuration = firstVisit ? 1600 : 450;
  const startTime = performance.now();
  let progress = 0;
  let finished = false;

  const render = (value) => {
    if (bar) bar.style.width = `${value}%`;
    if (count) count.textContent = Math.round(value);
  };

  // Ease toward 90% while assets load
  const ticker = setInterval(() => {
    progress += (90 - progress) * 0.08;
    render(progress);
  }, 60);

  const finish = () => {
    if (finished) return;
    finished = true;
    const wait = Math.max(0, minDuration - (performance.now() - startTime));

    setTimeout(() => {
      clearInterval(ticker);
      render(100);
      setTimeout(() => {
        loader.classList.add('loader-done');
        document.body.classList.remove('is-loading');
        document.body.classList.add('is-loaded');
        setTimeout(() => loader.remove(), 1000);
      }, 220);
    }, wait);
  };

  if (document.readyState === 'complete') {
    finish();
  } else {
    window.addEventListener('load', finish);
    // Failsafe: never hold the page hostage to a slow external image
    setTimeout(finish, 5000);
  }
}

/* ==========================================================
   1. PACKAGE PHOTO SLIDERS (PACKAGES PAGE)
   ========================================================== */
function initSliders() {
  const sliderContainers = document.querySelectorAll('.card-slider');

  sliderContainers.forEach((slider) => {
    const track = slider.querySelector('.slider-track');
    const slides = slider.querySelectorAll('.slider-slide');
    const prevBtn = slider.querySelector('.slider-prev');
    const nextBtn = slider.querySelector('.slider-next');
    const dotsContainer = slider.querySelector('.slider-dots');
    
    if (!track || slides.length === 0) return;

    let currentIndex = 0;
    const totalSlides = slides.length;

    // Create dots if dots container exists
    if (dotsContainer && dotsContainer.children.length === 0) {
      for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('span');
        dot.classList.add('slider-dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', (e) => {
          e.stopPropagation();
          goToSlide(i);
        });
        dotsContainer.appendChild(dot);
      }
    }

    const updateSlider = () => {
      track.style.transform = `translateX(-${currentIndex * 100}%)`;
      if (dotsContainer) {
        const dots = dotsContainer.querySelectorAll('.slider-dot');
        dots.forEach((dot, idx) => {
          dot.classList.toggle('active', idx === currentIndex);
        });
      }
    };

    const goToSlide = (index) => {
      currentIndex = (index + totalSlides) % totalSlides;
      updateSlider();
    };

    if (prevBtn) {
      prevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        goToSlide(currentIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        goToSlide(currentIndex + 1);
      });
    }

    // Auto slide every 5 seconds if not hovered
    let autoInterval = setInterval(() => {
      goToSlide(currentIndex + 1);
    }, 5500);

    slider.addEventListener('mouseenter', () => clearInterval(autoInterval));
    slider.addEventListener('mouseleave', () => {
      autoInterval = setInterval(() => {
        goToSlide(currentIndex + 1);
      }, 5500);
    });
  });
}

/* ==========================================================
   2. SIGN IN / CREATE ACCOUNT (MODAL + LOGIN PAGE)
   ========================================================== */
function initAuth() {
  document.querySelectorAll('[data-auth-panel]').forEach(initAuthPanel);

  const modal = document.getElementById('auth-modal');
  if (!modal) return;
  const dialogs = modal.querySelectorAll('[data-auth-dialog]');
  let lastFocus = null;

  // Shows the Sign In or Create Account dialog, swapping if one is already open
  const openModal = (mode) => {
    if (modal.hidden) lastFocus = document.activeElement;
    dialogs.forEach((dialog) => {
      dialog.hidden = dialog.dataset.authDialog !== mode;
      if (!dialog.hidden) dialog.querySelector('[data-auth-panel]').reset();
    });
    modal.hidden = false;
    document.body.classList.add('auth-modal-open');
    requestAnimationFrame(() => modal.classList.add('open'));
    modal.querySelector('[data-auth-dialog]:not([hidden]) .form-input').focus();
  };

  const closeModal = () => {
    modal.classList.remove('open');
    document.body.classList.remove('auth-modal-open');
    setTimeout(() => { if (!modal.classList.contains('open')) modal.hidden = true; }, 200);
    if (lastFocus) lastFocus.focus();
  };

  document.querySelectorAll('[data-auth-open]').forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelector('.main-header')?.classList.remove('nav-open');
      openModal(trigger.getAttribute('data-auth-open'));
    });
  });

  modal.querySelectorAll('[data-auth-close]').forEach((el) => el.addEventListener('click', closeModal));

  modal.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
      return;
    }
    if (e.key !== 'Tab') return;
    // Keep keyboard focus inside the dialog
    const focusable = [...modal.querySelectorAll('button, a[href], input, select')]
      .filter((el) => el.offsetParent !== null && !el.disabled);
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });
}

function initAuthPanel(panel) {
  const errorBox = panel.querySelector('[data-auth-error]');

  const showError = (message) => {
    errorBox.textContent = message || '';
    errorBox.hidden = !message;
  };

  panel.reset = () => showError('');

  panel.querySelectorAll('[data-password-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      btn.textContent = reveal ? 'Hide' : 'Show';
      btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
  });

  // Submit in the background so errors appear inline instead of reloading the page
  panel.querySelectorAll('.auth-form').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const invalid = [...form.querySelectorAll('.form-input')].find((input) => !input.checkValidity());
      if (invalid) {
        const name = form.querySelector(`label[for="${invalid.id}"]`).textContent.replace('(optional)', '').trim();
        showError(invalid.validity.valueMissing ? `${name} is required.`
          : invalid.validity.tooShort ? 'Your password must be at least 6 characters.'
          : `Please enter a valid ${name.toLowerCase()}.`);
        invalid.focus();
        return;
      }

      const submit = form.querySelector('.auth-submit');
      const label = submit.textContent;
      submit.disabled = true;
      submit.textContent = form.elements.action.value === 'signup' ? 'Creating account…' : 'Signing in…';
      showError('');

      // The hidden input named "action" shadows form.action, so read the attribute
      const url = new URL(form.getAttribute('action'), window.location.href);
      try {
        const res = await fetch(url, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        if (data.ok) {
          window.location.href = new URL(data.redirect, url).href;
          return;
        }
        showError(data.error || 'Something went wrong. Please try again.');
      } catch (err) {
        showError('Could not reach the server. Please try again.');
      }
      submit.disabled = false;
      submit.textContent = label;
    });
  });
}

/* ==========================================================
   3. EVENT BOOKING FORM & DYNAMIC CUSTOMIZER (BOOKING PAGE)
   ========================================================== */
function initBookingCalculator() {
  const bookingForm = document.getElementById('event-booking-form');
  if (!bookingForm) return;

  // Base Package details dictionary
  const packageData = {
    silver: {
      name: 'Silver Classic Photoman',
      basePrice: 6500,
      duration: 2,
      photographers: 1,
      operators: 1,
      printType: '4R Standard Glossy',
    },
    gold: {
      name: 'Gold Magnetic Keepsake',
      basePrice: 9500,
      duration: 3,
      photographers: 1,
      operators: 1,
      printType: '4R Magnetic Keepsake',
    },
    platinum: {
      name: 'Platinum VIP Deluxe Photoman',
      basePrice: 14500,
      duration: 4,
      photographers: 2,
      operators: 2,
      printType: '4R Magnetic + 2R Bookmark Strip',
    },
    custom: {
      name: 'Custom Bespoke Package',
      basePrice: 5000,
      duration: 2,
      photographers: 1,
      operators: 1,
      printType: 'Custom Format',
    },
  };

  // URL Params extraction for pre-selection
  const urlParams = new URLSearchParams(window.location.search);
  const pkgParam = (urlParams.get('package') || 'gold').toLowerCase();

  // Inputs
  const packageSelect = document.getElementById('package-select');
  const durationSlider = document.getElementById('duration-slider');
  const durationVal = document.getElementById('duration-val');
  const photographersSelect = document.getElementById('photographers-count');
  const operatorsSelect = document.getElementById('operators-count');
  const addonCheckboxes = document.querySelectorAll('.addon-checkbox');

  // Summary fields
  const summaryPkgName = document.getElementById('summary-pkg-name');
  const summaryBasePrice = document.getElementById('summary-base-price');
  const summaryExtraHoursRow = document.getElementById('summary-extra-hours-row');
  const summaryExtraHoursPrice = document.getElementById('summary-extra-hours-price');
  const summaryExtraStaffRow = document.getElementById('summary-extra-staff-row');
  const summaryExtraStaffPrice = document.getElementById('summary-extra-staff-price');
  const summaryAddonsRow = document.getElementById('summary-addons-row');
  const summaryAddonsPrice = document.getElementById('summary-addons-price');
  const summaryTotalPrice = document.getElementById('summary-total-price');
  const summaryFixedDp = document.getElementById('summary-fixed-dp');

  // Preselect from URL
  if (packageSelect) {
    if (packageData[pkgParam]) {
      packageSelect.value = pkgParam;
    } else {
      packageSelect.value = 'gold';
    }
  }

  const calculateTotal = () => {
    const selectedKey = packageSelect ? packageSelect.value : 'gold';
    const pkg = packageData[selectedKey] || packageData.gold;

    const currentDuration = parseInt(durationSlider?.value || pkg.duration);
    const currentPhotographers = parseInt(photographersSelect?.value || pkg.photographers);
    const currentOperators = parseInt(operatorsSelect?.value || pkg.operators);

    // Calculate extra hours (+₱1,500/hr above base)
    const extraHours = Math.max(0, currentDuration - pkg.duration);
    const extraHoursCost = extraHours * 1500;

    // Calculate extra photographers (+₱2,500/staff above base)
    const extraShooters = Math.max(0, currentPhotographers - pkg.photographers);
    const extraShootersCost = extraShooters * 2500;

    // Calculate extra print operators (+₱2,000/staff above base)
    const extraOps = Math.max(0, currentOperators - pkg.operators);
    const extraOpsCost = extraOps * 2000;

    const extraStaffCost = extraShootersCost + extraOpsCost;

    // Calculate add-ons
    let addonsCost = 0;
    if (addonCheckboxes) {
      addonCheckboxes.forEach((cb) => {
        if (cb.checked) {
          addonsCost += parseInt(cb.getAttribute('data-price') || '0');
        }
      });
    }

    const total = pkg.basePrice + extraHoursCost + extraStaffCost + addonsCost;
    const fixedDownPayment = 2000; // Fixed ₱2,000 as scoped in thesis

    // Update UI
    if (durationVal) durationVal.textContent = `${currentDuration} Hours`;
    if (summaryPkgName) summaryPkgName.textContent = pkg.name;
    if (summaryBasePrice) summaryBasePrice.textContent = `₱${pkg.basePrice.toLocaleString()}`;

    // Extra hours row
    if (summaryExtraHoursRow) {
      if (extraHours > 0) {
        summaryExtraHoursRow.style.display = 'flex';
        if (summaryExtraHoursPrice) summaryExtraHoursPrice.textContent = `+₱${extraHoursCost.toLocaleString()}`;
      } else {
        summaryExtraHoursRow.style.display = 'none';
      }
    }

    // Extra staff row
    if (summaryExtraStaffRow) {
      if (extraStaffCost > 0) {
        summaryExtraStaffRow.style.display = 'flex';
        if (summaryExtraStaffPrice) summaryExtraStaffPrice.textContent = `+₱${extraStaffCost.toLocaleString()}`;
      } else {
        summaryExtraStaffRow.style.display = 'none';
      }
    }

    // Addons row
    if (summaryAddonsRow) {
      if (addonsCost > 0) {
        summaryAddonsRow.style.display = 'flex';
        if (summaryAddonsPrice) summaryAddonsPrice.textContent = `+₱${addonsCost.toLocaleString()}`;
      } else {
        summaryAddonsRow.style.display = 'none';
      }
    }

    if (summaryTotalPrice) summaryTotalPrice.textContent = `₱${total.toLocaleString()}`;
    if (summaryFixedDp) summaryFixedDp.textContent = `₱${fixedDownPayment.toLocaleString()}`;
  };

  // Event Listeners for customizer
  if (packageSelect) {
    packageSelect.addEventListener('change', () => {
      const selected = packageData[packageSelect.value];
      if (selected && durationSlider) {
        durationSlider.value = selected.duration;
      }
      calculateTotal();
    });
  }

  if (durationSlider) {
    durationSlider.addEventListener('input', calculateTotal);
  }

  if (photographersSelect) {
    photographersSelect.addEventListener('change', calculateTotal);
  }

  if (operatorsSelect) {
    operatorsSelect.addEventListener('change', calculateTotal);
  }

  if (addonCheckboxes) {
    addonCheckboxes.forEach((cb) => cb.addEventListener('change', calculateTotal));
  }

  // Initial calculation
  calculateTotal();

  // Booking Form Submission (only mock if form lacks action)
  if (bookingForm && !bookingForm.getAttribute('action')) {
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();

    const clientName = document.getElementById('client-name')?.value || 'Valued Client';
    const clientEmail = document.getElementById('client-email')?.value || 'client@gmail.com';
    const eventName = document.getElementById('event-name')?.value || 'Special Event';
    const eventDate = document.getElementById('event-date')?.value || 'Upcoming Date';
    const bookingRef = `MIB-${new Date().getFullYear()}-${Math.floor(100 + Math.random() * 900)}`;

    const modal = document.getElementById('booking-confirmation-modal');
    const modalRef = document.getElementById('modal-booking-ref');
    const modalClient = document.getElementById('modal-client-name');
    const modalEmail = document.getElementById('modal-client-email');
    const modalEvent = document.getElementById('modal-event-name');

    if (modalRef) modalRef.textContent = bookingRef;
    if (modalClient) modalClient.textContent = clientName;
    if (modalEmail) modalEmail.textContent = clientEmail;
    if (modalEvent) modalEvent.textContent = `${eventName} (${eventDate})`;

    if (modal) {
      modal.classList.add('open');
    }
  });
}

  // Close modal
  const closeModalBtn = document.getElementById('modal-close-btn');
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      const modal = document.getElementById('booking-confirmation-modal');
      if (modal) modal.classList.remove('open');
      window.location.href = 'index.html';
    });
  }
}

/* ==========================================================
   4. USER SESSION HEADER DISPLAY
   ========================================================== */
function initNavigationUser() {
  const userGreeting = document.getElementById('nav-user-greeting');
  const loginBtn = document.getElementById('nav-login-btn');

  const storedUser = localStorage.getItem('mib_user');
  if (storedUser && userGreeting && loginBtn) {
    try {
      const user = JSON.parse(storedUser);
      userGreeting.textContent = `Hi, ${user.name}`;
      userGreeting.style.display = 'inline-block';
      loginBtn.textContent = 'Sign Out';
      loginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.removeItem('mib_user');
        window.location.reload();
      });
    } catch (e) {
      console.error(e);
    }
  }
}

/* ==========================================================
   5. MOBILE NAVIGATION TOGGLE
   ========================================================== */
function initMobileNav() {
  const header = document.querySelector('.main-header');
  const navbar = header?.querySelector('.navbar');
  if (!header || !navbar) return;

  let toggle = navbar.querySelector('.mobile-toggle');
  if (!toggle) {
    toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'mobile-toggle';
    toggle.innerHTML = '<span></span><span></span><span></span>';
    navbar.appendChild(toggle);
  }
  toggle.setAttribute('aria-label', 'Toggle navigation menu');
  toggle.setAttribute('aria-expanded', 'false');

  const setOpen = (open) => {
    header.classList.toggle('nav-open', open);
    toggle.setAttribute('aria-expanded', String(open));
  };

  toggle.addEventListener('click', () => setOpen(!header.classList.contains('nav-open')));
  header.querySelectorAll('.nav-link').forEach((link) => {
    link.addEventListener('click', () => setOpen(false));
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });
}

/* ==========================================================
   6. HEADER SHRINK ON SCROLL
   ========================================================== */
function initHeaderScroll() {
  const header = document.querySelector('.main-header');
  if (!header) return;

  const update = () => header.classList.toggle('scrolled', window.scrollY > 40);
  update();
  window.addEventListener('scroll', update, { passive: true });
}

/* ==========================================================
   7. SCROLL REVEAL ANIMATIONS
   ========================================================== */
function initScrollReveal() {
  if (!('IntersectionObserver' in window)) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const targets = document.querySelectorAll(
    '.section-header, .feature-card, .package-card, .packages-top-banner, ' +
    '.brand-placeholder-banner, .booking-form-box, .summary-card, .auth-wrapper .auth-card, .footer-grid > div, ' +
    '.review-card, .faq-list, .cta-card, .event-types'
  );

  // Stagger siblings in the same grid
  targets.forEach((el) => {
    const siblings = Array.from(el.parentElement.children).filter((c) => c.matches(el.tagName.toLowerCase() + '.' + el.classList[0]));
    const index = siblings.indexOf(el);
    if (index > 0) el.style.setProperty('--reveal-delay', `${Math.min(index, 5) * 0.08}s`);
    el.classList.add('reveal');
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  targets.forEach((el) => observer.observe(el));
}

/* ==========================================================
   8. HERO BACKGROUND VIDEO CONTROLS
   ========================================================== */
function initHeroVideo() {
  const video = document.querySelector('.hero-video');
  const toggle = document.getElementById('hero-video-toggle');
  if (!video) return;

  const icon = toggle?.querySelector('.toggle-icon');
  const syncButton = () => {
    if (!toggle) return;
    const paused = video.paused;
    if (icon) icon.textContent = paused ? '▶' : '❚❚';
    toggle.setAttribute('aria-label', paused ? 'Play background video' : 'Pause background video');
  };

  // Honor reduced-motion: show the still poster instead of motion
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    video.removeAttribute('autoplay');
    video.pause();
  } else {
    const playAttempt = video.play();
    if (playAttempt && playAttempt.catch) playAttempt.catch(() => syncButton());
  }

  video.addEventListener('play', syncButton);
  video.addEventListener('pause', syncButton);

  if (toggle) {
    toggle.addEventListener('click', () => {
      if (video.paused) video.play(); else video.pause();
    });
  }

  // Save battery/CPU: pause when the hero scrolls out of view
  if ('IntersectionObserver' in window) {
    let userPaused = false;
    toggle?.addEventListener('click', () => { userPaused = video.paused; });
    new IntersectionObserver(([entry]) => {
      if (userPaused) return;
      if (entry.isIntersecting) video.play().catch(() => {});
      else video.pause();
    }).observe(video);
  }

  syncButton();
}

/* ==========================================================
   9. BACK TO TOP BUTTON
   ========================================================== */
function initBackToTop() {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'back-to-top';
  btn.setAttribute('aria-label', 'Back to top');
  btn.textContent = '↑';
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    btn.classList.toggle('show', window.scrollY > 600);
  }, { passive: true });

  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}
