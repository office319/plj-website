/* ============================================
   WOW EFFECTS JavaScript - PolsterreiningungJuelich.de
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ========================================
    // 1. SCROLL PROGRESS BAR
    // ========================================
    let scrollProgress = document.querySelector('.scroll-progress');
    if (!scrollProgress) {
        scrollProgress = document.createElement('div');
        scrollProgress.className = 'scroll-progress';
        document.body.prepend(scrollProgress);
    }

    window.addEventListener('scroll', () => {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        scrollProgress.style.width = scrollPercent + '%';
    });

    // ========================================
    // 2. SPOTLIGHT CURSOR EFFECT
    // ========================================
    let spotlight = document.querySelector('.spotlight');
    if (!spotlight && window.innerWidth > 768) {
        spotlight = document.createElement('div');
        spotlight.className = 'spotlight';
        document.body.prepend(spotlight);
    }

    let mouseX = 0, mouseY = 0;
    let spotX = 0, spotY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    function animateSpotlight() {
        if (spotlight) {
            spotX += (mouseX - spotX) * 0.1;
            spotY += (mouseY - spotY) * 0.1;
            spotlight.style.left = spotX + 'px';
            spotlight.style.top = spotY + 'px';
        }
        requestAnimationFrame(animateSpotlight);
    }
    animateSpotlight();

    // ========================================
    // 3. MAGNETIC BUTTONS
    // ========================================
    document.querySelectorAll('.magnetic-btn').forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px) scale(1.05)`;
        });

        btn.addEventListener('mouseleave', () => {
            btn.style.transform = 'translate(0, 0) scale(1)';
        });
    });

    // ========================================
    // 4. TILT CARDS (3D)
    // ========================================
    document.querySelectorAll('.tilt-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        });
    });

    // ========================================
    // 5. INTERSECTION OBSERVER FOR ANIMATIONS
    // ========================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    // Stagger items
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.stagger-item, .reveal-left, .reveal-right, .reveal-up').forEach(el => {
        staggerObserver.observe(el);
    });

    // ========================================
    // 6. COUNTER ANIMATION
    // ========================================
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.counted) {
                entry.target.dataset.counted = 'true';
                const target = parseInt(entry.target.dataset.target) || parseInt(entry.target.textContent);
                const duration = 2000;
                const start = 0;
                const startTime = performance.now();

                const suffix = entry.target.dataset.suffix || '';
                const prefix = entry.target.dataset.prefix || '';

                function updateCounter(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeProgress = 1 - Math.pow(1 - progress, 3);
                    const current = Math.floor(start + (target - start) * easeProgress);
                    entry.target.textContent = prefix + current.toLocaleString() + suffix;

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    }
                }

                requestAnimationFrame(updateCounter);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.counter').forEach(el => {
        counterObserver.observe(el);
    });

    // ========================================
    // 7. BUTTON RIPPLE EFFECT
    // ========================================
    document.querySelectorAll('.btn-ripple').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';

            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
            ripple.style.top = e.clientY - rect.top - size / 2 + 'px';

            btn.appendChild(ripple);

            ripple.addEventListener('animationend', () => ripple.remove());
        });
    });

    // ========================================
    // 8. PARALLAX ON SCROLL
    // ========================================
    const parallaxElements = document.querySelectorAll('.parallax-layer');

    if (parallaxElements.length > 0) {
        window.addEventListener('scroll', () => {
            const scrollY = window.pageYOffset;
            parallaxElements.forEach(el => {
                const speed = el.dataset.speed || 0.5;
                el.style.transform = `translateY(${scrollY * speed}px)`;
            });
        });
    }

    // ========================================
    // 9. BUBBLE GENERATOR (cleaning theme)
    // ========================================
    document.querySelectorAll('.bubbles').forEach(container => {
        // Create random bubbles
        for (let i = 0; i < 10; i++) {
            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.style.width = Math.random() * 20 + 10 + 'px';
            bubble.style.height = bubble.style.width;
            bubble.style.left = Math.random() * 100 + '%';
            bubble.style.animationDelay = Math.random() * 4 + 's';
            bubble.style.animationDuration = Math.random() * 2 + 3 + 's';
            container.appendChild(bubble);
        }
    });

    // ========================================
    // 10. SMOOTH SCROLL FOR ANCHOR LINKS
    // ========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // ========================================
    // 11. LAZY LOAD IMAGES WITH FADE
    // ========================================
    document.querySelectorAll('img[data-src]').forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s ease';

        const imgObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.src = image.dataset.src;
                    image.onload = () => {
                        image.style.opacity = '1';
                    };
                    imgObserver.unobserve(image);
                }
            });
        });

        imgObserver.observe(img);
    });

    console.log('✨ WOW Effects loaded - PolsterreiningungJuelich.de');
});
