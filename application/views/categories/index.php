<style>
	.category-actions { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; margin:1rem 0; }
	.categories-table { width:100%; border-collapse:collapse; }
	.categories-table th, .categories-table td { text-align:left; padding:.75rem; border-bottom:1px solid var(--border); overflow-wrap:anywhere; }
	.category-link { color:#fdba74; }
	.category-button { padding:.5rem .9rem; border:1px solid var(--border); border-radius:6px; cursor:pointer; }
</style>
<h2>Kategori</h2>
<?php if ($message = $this->session->flashdata('success')): ?>
	<p role="status"><?= e($message) ?></p>
<?php endif; ?>
<div class="category-actions"><a class="category-link" href="<?= e(site_url('categories/create')) ?>">Tambah kategori</a></div>
<table class="categories-table">
	<thead><tr><th scope="col">Nama</th><th scope="col">Deskripsi</th><th scope="col">Aksi</th></tr></thead>
	<tbody>
	<?php foreach ($pagination['data'] as $category): ?>
		<tr>
			<td><?= e($category->name) ?></td><td><?= e($category->description) ?></td>
			<td><div class="category-actions">
				<a class="category-link" href="<?= e(site_url('categories/edit/' . $category->id)) ?>">Edit</a>
				<form method="post" action="<?= e(site_url('categories/delete/' . $category->id)) ?>" onsubmit="return confirm('Hapus kategori ini?')">
					<?= csrf_field() ?><button class="category-button" type="submit" aria-label="<?= e('Hapus ' . $category->name) ?>">Hapus</button>
				</form>
			</div></td>
		</tr>
	<?php endforeach; ?>
	<?php if (!$pagination['data']): ?><tr><td colspan="3">Belum ada kategori di halaman ini.</td></tr><?php endif; ?>
	</tbody>
</table>
<nav class="category-actions" aria-label="Halaman kategori">
	<?php if ($pagination['current_page'] > 1): ?><a class="category-link" href="<?= e(site_url('categories') . '?page=' . ($pagination['current_page'] - 1)) ?>">Sebelumnya</a><?php endif; ?>
	<span>Halaman <?= e($pagination['current_page']) ?> · <?= e($pagination['total']) ?> kategori</span>
	<?php if ($pagination['current_page'] < $pagination['last_page']): ?><a class="category-link" href="<?= e(site_url('categories') . '?page=' . ($pagination['current_page'] + 1)) ?>">Berikutnya</a><?php endif; ?>
</nav>
