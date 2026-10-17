<?php

defined('BASEPATH') or exit('No direct script access allowed');

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
		$page = filter_var($this->input->get('page'), FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 1, 'max_range' => 1000000],
		]);
		if ($page === false) {
			$page = 1;
		}
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
		$values = [
			'name' => $category->name ?? '',
			'description' => $category->description ?? '',
		];

		if ($this->input->method() !== 'post') {
			$this->renderForm($category, $values);
			return;
		}

		$errors = [];
		foreach (['name', 'description'] as $field) {
			$posted = $this->input->post($field);
			$values[$field] = '';

			if (is_string($posted)) {
				$values[$field] = $posted;
			} elseif ($posted !== null) {
				$errors[$field] = 'Isi dengan teks biasa.';
			}
		}

		try {
			if ($errors) {
				throw new ValidationException($errors);
			}

			$validated = $this->validate([
				'name' => ['label' => 'Nama', 'rules' => 'trim|required|max_length[80]'],
				'description' => ['label' => 'Deskripsi', 'rules' => 'trim|max_length[500]'],
			], $values);

			if ($category !== null) {
				$saved = $this->categories->update($category->id, $validated);
			} else {
				$saved = $this->categories->create($validated);
			}

			if ($saved === false) {
				$this->writeFailed();
			}

			$this->session->set_flashdata('success', 'Kategori berhasil disimpan.');
			redirect('categories', 'location', 303);
		} catch (ValidationException $e) {
			$this->output->set_status_header(422);
			$this->renderForm($category, $values, $e->getErrors());
		}
	}

	protected function renderForm(?object $category, array $values, array $errors = []): void
	{
		$title = 'Tambah kategori';
		$action = 'categories/create';
		if ($category !== null) {
			$title = 'Edit kategori';
			$action = 'categories/edit/' . $category->id;
		}

		$this->renderLayout('categories/form', [
			'title' => $title,
			'action' => $action,
			'values' => $values,
			'errors' => $errors,
		]);
	}

	protected function findCategory($id): object
	{
		$id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) {
			show_404();
		}

		$category = $this->categories->find($id);
		if (!$category) {
			show_404();
		}
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
