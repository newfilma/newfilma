// assets/js/app.js (Versioni i fundit i rregulluar)

// Funksioni Debounce
function debounce(func, delay) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), delay);
  };
}

// Marrim filmat nga PHP
const MOVIES = Array.isArray(window.__MOVIES__) ? window.__MOVIES__ : [];

// Gjendja e filtrave
let currentCategory = 'all';
let currentSearch = '';

// Map kategori -> label për afishim
const CATEGORY_LABELS = {
  aksion: 'Aksion',
  komedi: 'Komedi',
  drame: 'Dramë',
  horror: 'Horror',
  thriller: 'Thriller',
  scifi: 'Sci-Fi',
  aventura: 'Aventurë',
  fantazi: 'Fantazi',
  krim: 'Krim',
  romance: 'Romancë',
  animacion: 'Animacion',
  familjar: 'Familjar',
  dokumentar: 'Dokumentar'
};

const moviesGrid = document.getElementById('moviesGrid');
const searchInput = document.getElementById('searchInput');

// 🔹 Renderimi kryesor i filmave
function renderMovies() {
  if (!moviesGrid) return;

  let items = MOVIES.slice();

  // 1. Filtro sipas kategorisë
  if (currentCategory !== 'all') {
    items = items.filter(m => (m.category || 'all') === currentCategory);
  }

  // 2. Filtro sipas kërkimit
  if (currentSearch.trim() !== '') {
    const q = currentSearch.toLowerCase();
    items = items.filter(m => {
      const t = (m.title || '').toLowerCase();
      const d = (m.description || '').toLowerCase();
      return t.includes(q) || d.includes(q);
    });
  }

  // 3. Sorto sipas ID (më i riu i pari)
  items.sort((a, b) => (b.id || 0) - (a.id || 0));

  if (items.length === 0) {
    moviesGrid.innerHTML =
      '<p style="padding:10px 0; color:#d1d5db;">Nuk u gjet asnjë film me këto filtre.</p>';
    return;
  }

  const html = items
    .map(m => {
      const title = m.title || 'Pa Titull';
      const year = m.year || '';
      const rating = m.rating || '';
      const catKey = m.category || '';
      const catLabel = CATEGORY_LABELS[catKey] || catKey;
      const poster = m.poster || '';
      
      // ... rreth rreshtit 136 në app.js (brenda items.map)

      // Struktura e saktë e thjeshtuar HTML për kartën e filmit (PA përshkrim dhe PA butona)
      return `
        <a href="movie.php?id=${m.id}" class="movie-card">
          <div class="movie-poster" style="${poster ? `background-image: url('${poster}');` : 'background-color: #1e293b;'}">
            ${
              rating
                ? `<div class="movie-badge">${rating} ⭐</div>`
                : ''
            }
            ${
              m.quality
                ? `<div class="movie-quality">${m.quality}</div>`
                : ''
            }
          </div>
          <div class="movie-info">
            <h3 class="movie-title">${title}</h3>
            <p class="movie-meta">
              ${year ? `${year} · ` : ''}${catLabel || ''}
            </p>
          </div>
        </a>
      `;
    })
    .join('');

  moviesGrid.innerHTML = html;
}

// 🔹 Menaxhimi i tabs (poshtë "Filma Popullor")
function setupCategoryTabs() {
  const tabs = document.querySelectorAll('.category-tabs .tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentCategory = tab.getAttribute('data-category') || 'all';
      renderMovies();
      scrollToMoviesSmooth();
    });
  });
}

// 🔹 Kërkimi (search bar në header)
function setupSearch() {
  if (!searchInput) return;

  const debouncedSearch = debounce(() => {
    currentSearch = searchInput.value || '';
    renderMovies();
  }, 300); // Prit 300ms

  searchInput.addEventListener('input', debouncedSearch);
}

// 🔹 Scroll i butë te seksioni i filmave
function scrollToMoviesSmooth() {
  const section = document.getElementById('moviesSection');
  if (!section) return;
  section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// që të punojë butoni në hero: onclick="scrollToMovies()"
window.scrollToMovies = scrollToMoviesSmooth;


// 🔹 Vendos vitin në footer
(function setYear() {
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();
})();


// 🔹 INIT
document.addEventListener('DOMContentLoaded', () => {
  setupCategoryTabs();
  // setupGenreMenu(); // Komentuar sepse ky nuk është në HTML
  setupSearch();
  renderMovies();
});