<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class SQLLog extends Model {
		
		protected $table = 'sqllog';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'class', 'function', 'sql', 'error', 'created'];
		
		public $timestamps = false;
		
	}
