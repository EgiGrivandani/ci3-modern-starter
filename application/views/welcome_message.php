<style>
	.grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		gap: 1.25rem;
		margin-bottom: 1.5rem;
	}
	.card {
		background: var(--card);
		border: 1px solid var(--border);
		border-radius: 12px;
		padding: 1.35rem;
	}
	.card h2 {
		font-size: 1.05rem;
		margin-bottom: 0.75rem;
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.card p {
		color: var(--muted);
		font-size: 0.9rem;
		margin-bottom: 0.75rem;
	}
	.meta-list {
		list-style: none;
		font-size: 0.9rem;
	}
	.meta-list li {
		display: flex;
		justify-content: space-between;
		padding: 0.45rem 0;
		border-bottom: 1px dashed var(--border);
	}
	.meta-list li:last-child { border-bottom: none; }
	.meta-label { color: var(--muted); }
	.meta-val { font-family: Consolas, monospace; color: #38bdf8; }
	pre.code {
		background: var(--code-bg);
		border: 1px solid var(--border);
		padding: 0.85rem;
		border-radius: 8px;
		font-family: Consolas, Monaco, monospace;
		font-size: 0.82rem;
		color: #e2e8f0;
		overflow-x: auto;
	}
	.btn {
		display: inline-block;
		background: var(--accent);
		color: #fff;
		border: none;
		padding: 0.55rem 1rem;
		border-radius: 8px;
		font-weight: 600;
		font-size: 0.85rem;
		cursor: pointer;
		text-decoration: none;
		transition: background 0.15s;
	}
	.btn:hover { background: var(--accent-hover); }
</style>

<div class="grid">
	<div class="card">
		<h2>Environment Info (via PSR-4 Service)</h2>
		<p>Data di bawah ini diambil langsung dari class <code>App\Services\AppInfoService</code> dan di-escape dengan <code>e()</code>:</p>
		<ul class="meta-list">
			<li><span class="meta-label">App Name (.env)</span><span class="meta-val"><?= e($info['app_name'] ?? '') ?></span></li>
			<li><span class="meta-label">Environment</span><span class="meta-val"><?= e($info['environment'] ?? '') ?></span></li>
			<li><span class="meta-label">PHP Version</span><span class="meta-val"><?= e($info['php_version'] ?? '') ?></span></li>
			<li><span class="meta-label">CI3 Version</span><span class="meta-val"><?= e($info['ci_version'] ?? '') ?></span></li>
			<li><span class="meta-label">Timezone</span><span class="meta-val"><?= e($info['timezone'] ?? '') ?></span></li>
			<li><span class="meta-label">Server Time</span><span class="meta-val"><?= e($info['server_time'] ?? '') ?></span></li>
			<li><span class="meta-label">Base URL</span><span class="meta-val"><?= e($info['base_url'] ?? '') ?></span></li>
		</ul>
	</div>

	<div class="card">
		<h2>Test JSON API Endpoint</h2>
		<p>Contoh response JSON menggunakan <code>$this->json($data, 200)</code> dari <code>MY_Controller</code> pada route <code>GET /api/health</code>:</p>
		<p>
			<button type="button" class="btn" id="btnTestApi">Panggil GET /api/health</button>
			<a href="<?= base_url('api/health') ?>" target="_blank" style="margin-left:0.5rem;color:#38bdf8;font-size:0.85rem;">Buka di Tab Baru &rarr;</a>
		</p>
		<pre class="code" id="apiOutput">// Klik tombol di atas untuk melihat response JSON...</pre>
	</div>
</div>

<div class="grid">
	<div class="card">
		<h2>1. Controller & Layout (MY_Controller)</h2>
		<p>Lokasi: <code>application/controllers/Welcome.php</code></p>
		<pre class="code">class Welcome extends Public_Controller
{
    public function index()
    {
        $service = new \App\Services\AppInfoService();
        $this->renderLayout('welcome_message', [
            'title' => 'Dashboard',
            'info'  => $service->getSummary(),
        ]);
    }
}</pre>
	</div>

	<div class="card">
		<h2>2. Model CRUD & Fillable (MY_Model)</h2>
		<p>Lokasi: <code>application/models/User_model.php</code></p>
		<pre class="code">$this->load->model('User_model', 'userModel');

// Ambil semua data & cari berdasarkan ID
$users = $this->userModel->all(['is_active' => 1]);
$user  = $this->userModel->find(1);

// Insert & otomatis isi created_at + updated_at
$id = $this->userModel->create([
    'name'  => 'Budi Santoso',
    'email' => 'budi@example.com',
]);</pre>
	</div>
</div>

<?php if (in_array(ENVIRONMENT, ['development', 'testing'], true)): ?>
<p style="margin-top:1rem"><a style="color:#fdba74" href="<?= e(site_url('categories')) ?>">Buka contoh CRUD kategori</a></p>
<?php endif; ?>

<script>
document.getElementById('btnTestApi')?.addEventListener('click', async function () {
	const out = document.getElementById('apiOutput');
	out.textContent = 'Loading...';
	try {
		const res = await fetch('<?= base_url('api/health') ?>');
		const data = await res.json();
		out.textContent = JSON.stringify(data, null, 2);
	} catch (err) {
		out.textContent = 'Error: ' + err.message;
	}
});
</script>
