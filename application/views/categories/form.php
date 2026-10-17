<style>
	.category-form { max-width:600px; }
	.category-form label { display:block; margin-top:1rem; }
	.category-form input, .category-form textarea { width:100%; padding:.7rem; border:1px solid var(--border); border-radius:6px; font:inherit; }
	.category-form button { margin:1rem 1rem 0 0; padding:.7rem 1.2rem; cursor:pointer; }
	.category-form a { color:#fdba74; }
	.field-error { color:#fca5a5; }
</style>
<h2><?= e($title) ?></h2>
<form class="category-form" method="post" action="<?= e(site_url($action)) ?>">
	<?= csrf_field() ?>
	<label for="name">Nama</label>
	<input id="name" name="name" value="<?= e($values['name']) ?>" required maxlength="80" aria-describedby="name-error" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
	<p class="field-error" id="name-error"><?= e($errors['name'] ?? '') ?></p>
	<label for="description">Deskripsi (opsional)</label>
	<textarea id="description" name="description" rows="4" maxlength="500" aria-describedby="description-error" aria-invalid="<?= isset($errors['description']) ? 'true' : 'false' ?>"><?= e($values['description']) ?></textarea>
	<p class="field-error" id="description-error"><?= e($errors['description'] ?? '') ?></p>
	<button type="submit">Simpan</button><a href="<?= e(site_url('categories')) ?>">Batal</a>
</form>
