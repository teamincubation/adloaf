/**
 * ADLOAF - Portfolio Website Interactive Script
 */

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initMobileMenu();
  initScrollReveal();
  initPortfolioFilter();
  initContactForm();
  initSmoothScrollActiveState();
});

/**
 * 1. Sticky Header Control
 */
function initStickyHeader() {
  const header = document.getElementById('header');
  
  if (!header) return;

  const handleScroll = () => {
    if (window.scrollY > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  // Run on load and on scroll
  handleScroll();
  window.addEventListener('scroll', handleScroll, { passive: true });
}

/**
 * 2. Mobile Menu Toggle
 */
function initMobileMenu() {
  const menuToggle = document.getElementById('menu-toggle');
  const navMenu = document.getElementById('nav-menu');
  const navLinks = document.querySelectorAll('.nav-link');
  
  if (!menuToggle || !navMenu) return;

  const toggleMenu = () => {
    const isOpen = navMenu.classList.contains('open');
    navMenu.classList.toggle('open');
    menuToggle.classList.toggle('active');
    menuToggle.setAttribute('aria-expanded', !isOpen);
  };

  const closeMenu = () => {
    navMenu.classList.remove('open');
    menuToggle.classList.remove('active');
    menuToggle.setAttribute('aria-expanded', 'false');
  };

  menuToggle.addEventListener('click', toggleMenu);

  // Close menu when clicking navigation links
  navLinks.forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  // Close menu when clicking outside of nav menu
  document.addEventListener('click', (event) => {
    const isClickInside = navMenu.contains(event.target) || menuToggle.contains(event.target);
    if (!isClickInside && navMenu.classList.contains('open')) {
      closeMenu();
    }
  });
}

/**
 * 3. Scroll Reveal Animation (Intersection Observer)
 */
function initScrollReveal() {
  const revealElements = document.querySelectorAll('.reveal');
  
  if (revealElements.length === 0) return;

  const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.12 // Trigger when 12% of the element is visible
  };

  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
        // Once revealed, we can stop observing it
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  revealElements.forEach(element => {
    revealObserver.observe(element);
  });
}

/**
 * 4. Portfolio Grid Filtering
 */
function initPortfolioFilter() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const portfolioItems = document.querySelectorAll('.portfolio-item');
  
  if (filterBtns.length === 0 || portfolioItems.length === 0) return;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      // 1. Remove active class from all buttons and add to clicked button
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      // 2. Filter the items
      portfolioItems.forEach(item => {
        const itemCategory = item.getAttribute('data-category');

        if (filterValue === 'all' || itemCategory === filterValue) {
          // Show item
          item.classList.remove('hidden');
          // Mini timeout to trigger scaling transition smoothly
          setTimeout(() => {
            item.classList.remove('fade-out');
          }, 50);
        } else {
          // Hide item
          item.classList.add('fade-out');
          item.classList.add('hidden');
        }
      });
    });
  });
}

/**
 * 5. Dynamic Form Validation & Playful Baking Submit
 */
function initContactForm() {
  const form = document.getElementById('contact-form');
  const submitBtn = document.getElementById('form-submit-btn');
  const successBox = document.getElementById('form-success-box');

  if (!form || !submitBtn || !successBox) return;

  // Real-time input validation borders
  const inputs = form.querySelectorAll('.form-input, .form-textarea');
  inputs.forEach(input => {
    input.addEventListener('blur', () => {
      if (input.checkValidity()) {
        input.style.borderColor = 'rgba(16, 185, 129, 0.4)'; // Soft green for valid
      } else {
        input.style.borderColor = 'rgba(239, 68, 68, 0.4)'; // Soft red for invalid
      }
    });

    input.addEventListener('input', () => {
      // Revert to theme focus outline when editing
      input.style.borderColor = '';
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    // Reset styles
    inputs.forEach(input => input.style.borderColor = '');

    // Check validity
    if (!form.checkValidity()) {
      // Show browser tooltips/error states
      form.reportValidity();
      return;
    }

    // Change button state to "Baking..."
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Baking your message... 🥣🔥';
    submitBtn.style.opacity = '0.75';

    // Simulate oven bake time (1.8s)
    setTimeout(() => {
      // Reset button
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;
      submitBtn.style.opacity = '';

      // Show success alert
      successBox.classList.add('success');
      form.reset();

      // Smooth scroll success alert into view
      successBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

      // Clear success notification after 7 seconds
      setTimeout(() => {
        successBox.classList.remove('success');
      }, 7000);
    }, 1800);
  });
}

/**
 * 6. Active Nav Scroll Sync
 */
function initSmoothScrollActiveState() {
  const sections = document.querySelectorAll('section');
  const navLinks = document.querySelectorAll('.nav-link');

  if (sections.length === 0 || navLinks.length === 0) return;

  const observerOptions = {
    root: null,
    rootMargin: '-30% 0px -60% 0px', // Trigger when section occupies the sweet spot of viewport
    threshold: 0
  };

  const navObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        
        navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href') === `#${id}`) {
            link.classList.add('active');
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach(section => {
    navObserver.observe(section);
  });
}
