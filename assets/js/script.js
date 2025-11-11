// Hero Slider Functionality
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;

// Next Slide
document.querySelector('.next').addEventListener('click', () => {
  slides[currentSlide].classList.remove('active');
  currentSlide = (currentSlide + 1) % totalSlides;
  slides[currentSlide].classList.add('active');
});

// Previous Slide
document.querySelector('.prev').addEventListener('click', () => {
  slides[currentSlide].classList.remove('active');
  currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
  slides[currentSlide].classList.add('active');
});

// Auto slide every 5 seconds
setInterval(() => {
  slides[currentSlide].classList.remove('active');
  currentSlide = (currentSlide + 1) % totalSlides;
  slides[currentSlide].classList.add('active');
}, 5000);

// Optional: Keyboard navigation
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowLeft') {
    document.querySelector('.prev').click();
  } else if (e.key === 'ArrowRight') {
    document.querySelector('.next').click();
  }
});

// Toggle Switch Functionality
const toggleButtons = document.querySelectorAll('.toggle-btn');
const newProducts = document.getElementById('new-products');
const refurbishedProducts = document.getElementById('refurbished-products');

toggleButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    // Remove active class from all buttons
    toggleButtons.forEach(b => b.classList.remove('active'));
    
    // Add active class to clicked button
    btn.classList.add('active');
    
    // Show/hide product sections
    const category = btn.getAttribute('data-category');
    
    if (category === 'new') {
      newProducts.style.display = 'grid';
      refurbishedProducts.style.display = 'none';
    } else {
      newProducts.style.display = 'none';
      refurbishedProducts.style.display = 'grid';
    }
  });
});