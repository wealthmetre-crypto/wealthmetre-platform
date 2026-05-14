// Minimal example usage from a web page
async function runExample() {
  const input = document.getElementById('q');
  const out = document.getElementById('out');
  const q = input.value.trim();
  if (!q) return;
  out.textContent = 'Loading...';
  const res = await fetch('../api/lender_match.php?q=' + encodeURIComponent(q));
  const data = await res.json();
  out.textContent = JSON.stringify(data, null, 2);
}
