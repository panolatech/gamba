<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class EnrollSheets extends Model {
		
		protected $table = 'enrollsheets';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'term', 'camp'];
		
		public $timestamps = false;
		
	}
