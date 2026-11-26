(() => {
  const form = document.getElementById('filters');
  if (!form) return;
  const output = document.getElementById('games');
  const count = document.getElementById('result-count');
  let timer;
  let controller;
  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  async function search() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      controller?.abort();
      controller = new AbortController();
      const params = new URLSearchParams(new FormData(form));
      try {
        const response = await fetch(`search.php?${params}`, {headers: {'Accept':'application/json'}, signal: controller.signal});
        if (!response.ok) throw new Error('Search failed');
        const data = await response.json();
        count.textContent = `${data.length} game${data.length === 1 ? '' : 's'}`;
        output.innerHTML = data.length ? data.map(game => `<div class="col-md-6 col-lg-4"><article class="card h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2"><h2 class="h5">${esc(game.game_name)}</h2><span class="badge text-bg-primary align-self-start">${esc(game.rating)}/100</span></div><p class="small text-secondary">Released ${esc(game.released_date)}</p><p>${esc(game.short_description)}</p><a href="game.php?id=${encodeURIComponent(game.game_id)}" class="btn btn-sm btn-outline-primary">Details</a></div></article></div>`).join('') : '<div class="col"><p class="text-secondary">No games found.</p></div>';
      } catch (error) { if (error.name !== 'AbortError') count.textContent = 'Search could not be completed.'; }
    }, 180);
  }
  form.addEventListener('input', search);
  form.addEventListener('reset', () => setTimeout(search, 0));
})();
