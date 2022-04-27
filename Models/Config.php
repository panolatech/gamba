<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Config extends Model {
		
		protected $table = 'config';

		protected $primaryKey = 'field';
		
		public $incrementing = false;
		
		protected $fillable = ['id', 'description', 'field', 'value', 'editable'];
		
		public $timestamps = false;
		
	}