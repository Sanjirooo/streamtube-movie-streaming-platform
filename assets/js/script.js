/**
 * Streamtube JavaScript Functions
 */

// Navbar Scroll Effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Mobile Menu Toggle
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    if (navMenu) navMenu.classList.toggle('active');
}

// User Dropdown Toggle
function toggleUserMenu() {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) userDropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu && !userMenu.contains(e.target)) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.classList.remove('active');
    }
});

// Hero Slider
let currentSlide = 0;
let autoSlideInterval;

function showSlide(index) {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    if (slides.length === 0) return;

    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (dots[i]) dots[i].classList.remove('active');
    });

    currentSlide = index;
    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;

    if (slides[currentSlide]) slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].classList.add('active');
}

function nextSlide() {
    showSlide(currentSlide + 1);
}

function goToSlide(index) {
    showSlide(index);
    resetAutoSlide();
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    autoSlideInterval = setInterval(nextSlide, 5000);
}

// Start auto slide
const initialSlides = document.querySelectorAll('.hero-slide');
if (initialSlides.length > 0) {
    autoSlideInterval = setInterval(nextSlide, 5000);
}

// Carousel Navigation
function scrollCarousel(carouselId, direction) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;

    const container = carousel.querySelector('.carousel-container');
    if (!container) return;

    const scrollAmount = 300;
    container.scrollBy({
        left: scrollAmount * direction,
        behavior: 'smooth'
    });
}

// My List Toggle
function toggleMyList(movieId, button) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/mylist.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    if (response.action === 'added') {
                        button.classList.add('added');
                        button.innerHTML = '<i class="fas fa-check"></i> Added';
                    } else {
                        button.classList.remove('added');
                        button.innerHTML = '<i class="fas fa-plus"></i> My List';
                    }
                } else if (response.not_logged_in) {
                    window.location.href = 'login.php';
                }
            } catch (e) {
                console.error('JSON parse error', e);
            }
        }
    };

    xhr.send('movie_id=' + movieId);
}

// Rating System
function setRating(rating, movieId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/rating.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateStars(rating);
                    const avgDisplay = document.querySelector('.avg-rating');
                    if (avgDisplay) {
                        const numEl = avgDisplay.querySelector('.rating-num');
                        const countEl = avgDisplay.querySelector('.rating-count');
                        if (numEl) numEl.textContent = parseFloat(response.avg_rating).toFixed(1);
                        if (countEl) countEl.textContent = '(' + response.rating_count + ' ratings)';
                    }
                } else if (response.not_logged_in) {
                    alert('Login dulu untuk memberi rating.');
                }
            } catch (e) {
                console.error('JSON parse error', e);
            }
        }
    };

    xhr.send('movie_id=' + movieId + '&rating=' + rating);
}

function updateStars(rating) {
    const stars = document.querySelectorAll('.star-rating button');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Comment Form
function submitComment(movieId) {
    const commentInput = document.querySelector('.comment-form textarea');
    if (!commentInput) return;

    const comment = commentInput.value.trim();
    if (!comment) {
        alert('Please enter a comment');
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/comment.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.comment) {
                    addCommentToList(response.comment);
                    commentInput.value = '';
                } else if (response.not_logged_in) {
                    alert('Login dulu untuk bisa komen ya.');
                }
            } catch (e) {
                console.error('JSON parse error', e);
            }
        }
    };

    xhr.send('movie_id=' + movieId + '&comment=' + encodeURIComponent(comment));
}

function addCommentToList(comment) {
    const commentsList = document.querySelector('.comments-list');
    if (!commentsList) return;

    const badge = comment.membership_badge || '';
    const banner = comment.comment_banner ? `<div class="comment-member-banner">${comment.comment_banner}</div>` : '';
    const displayName = comment.has_membership ? `${comment.user_name} <span class="membership-name-inline">(${comment.membership_name})</span>` : comment.user_name;

    const commentHtml = `
        <div class="comment-item">
            <div class="comment-header">
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(comment.user_name)}&background=e50914&color=fff" alt="${comment.user_name}">
                <div>
                    <div class="comment-user">${displayName} ${badge}</div>
                    ${banner}
                    <div class="comment-date">${comment.created_at}</div>
                </div>
            </div>
            <div class="comment-text">${comment.comment}</div>
        </div>
    `;
    commentsList.insertAdjacentHTML('afterbegin', commentHtml);
}

// Add to history
function addToHistory(movieId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/history.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.send('movie_id=' + movieId);
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Tab Navigation
function openTab(tabId) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));

    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    const targetTab = document.getElementById(tabId);
    if (targetTab) targetTab.classList.add('active');

    const targetBtn = document.querySelector(`[onclick="openTab('${tabId}')"]`);
    if (targetBtn) targetBtn.classList.add('active');
}

// Smooth Scroll for Navigation
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        const targetId = link.getAttribute('href');
        if (targetId && targetId.startsWith('#')) {
            e.preventDefault();
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });

                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                const navMenu = document.querySelector('.nav-menu');
                if (navMenu) navMenu.classList.remove('active');
            }
        }
    });
});

// Console Info
console.log('%c🎬 Streamtube', 'font-size: 24px; font-weight: bold; color: #e50914;');
console.log('%cYour favorite streaming platform', 'font-size: 14px; color: #b3b3b3;');