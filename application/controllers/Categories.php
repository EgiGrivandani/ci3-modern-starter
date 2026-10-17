<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Exceptions\ValidationException;

/** Development example. Apply your project's authentication/authorization before enabling in production. */
class Categories extends Public_Controller
{
	public function __construct()
	{
		parent::__construct();
		if (!in_array(ENVIRONMENT, ['development', 'testing'], true)) {
			show_404();
		}
		$this->load->database();
		$this->db->db_debug = false;
		$this->load->model('Category_model', 'categories');
	}

	public function index(): void
	{
		$this->allowMethods(['GET']);
		$page = filter_var($this->input->get('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]) ?: 1;
		$this->renderLayout('categories/index', [
			'title' => 'Kategori',
			'pagination' => $this->categories->paginate(10, $page, [], 'id DESC'),
		]);
	}

	public function create(): void
	{
		$this->allowMethods(['GET', 'POST']);
		$this->form();
	}

	public function edit($id = null): void
	{
		$this->allowMethods(['GET', 'POST']);
		$this->form($this->findCategory($id));
	}

	public function delete($id = null): void
	{
		$this->allowMethods(['POST']);
		$category = $this->findCategory($id);
		if (!$this->categories->delete($category->id)) {
			$this->writeFailed();
		}
		$this->session->set_flashdata('success', 'Kategori berhasil dihapus.');
		redirect('categories', 'location', 303);
	}

	protected function form(?object $category = null): void
	{
		$values = ['name' => $category->name ?? '', 'description' => $category->description ?? ''];
		$errors = [];
		if ($this->input->method() === 'post') {
			foreach ($values as $field => $value) {
				$posted = $this->input->post($field);
				$values[$field] = is_string($posted) ? $posted : '';
				if ($posted !== null && !is_string($posted)) {
					$errors[$field] = 'Isi dengan teks biasa.';
				}
			}
			try {
				if ($errors) throw new ValidationException($errors);
				$validated = $this->validate([
					'name' => ['label' => 'Nama', 'rules' => 'trim|required|max_length[80]'],
					'description' => ['label' => 'Deskripsi', 'rules' => 'trim|max_length[500]'],
				], $values);
				$saved = $category
					? $this->categories->update($category->id, $validated)
					: $this->categories->create($validated);
				if ($saved === false) $this->writeFailed();
				$this->session->set_flashdata('success', 'Kategori berhasil disimpan.');
				redirect('categories', 'location', 303);
			} catch (ValidationException $e) {
				$errors = $e->getErrors();
				$this->output->set_status_header(422);
			}
		}
		$this->renderLayout('categories/form', [
			'title' => $category ? 'Edit kategori' : 'Tambah kategori',
			'action' => $category ? 'categories/edit/' . $category->id : 'categories/create',
			'values' => $values,
			'errors' => $errors,
		]);
	}

	protected function findCategory($id): object
	{
		$id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$category = $id ? $this->categories->find($id) : null;
		if (!$category) show_404();
		return $category;
	}

	protected function allowMethods(array $methods): void
	{
		if (!in_array(strtoupper($this->input->method()), $methods, true)) {
			header('Allow: ' . implode(', ', $methods));
			show_error('Metode request tidak diizinkan.', 405);
		}
	}

	protected function writeFailed(): void
	{
		log_context('error', 'Category write failed.', ['database' => $this->db->error()]);
		show_error('Data belum berhasil disimpan. Silakan coba kembali.', 500);
	}
}
