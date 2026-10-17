<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_categories extends CI_Migration
{
	public function up(): void
	{
		$this->dbforge->add_field([
			'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
			'name' => ['type' => 'VARCHAR', 'constraint' => 80],
			'description' => ['type' => 'TEXT', 'null' => true],
			'created_at' => ['type' => 'DATETIME'],
			'updated_at' => ['type' => 'DATETIME'],
		]);
		$this->dbforge->add_key('id', true);
		if (!$this->dbforge->create_table('categories')) {
			throw new RuntimeException('Could not create categories table.');
		}
	}

	public function down(): void
	{
		if (!$this->dbforge->drop_table('categories')) {
			throw new RuntimeException('Could not drop categories table.');
		}
	}
}
