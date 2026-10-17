<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Category_model extends MY_Model
{
	protected string $table = 'categories';
	protected array $fillable = ['name', 'description'];
}
