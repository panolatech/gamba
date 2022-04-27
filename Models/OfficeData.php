<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class OfficeData extends Model {
		
		protected $table = 'officedata';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'term', 'sheet_id', 'camp', 'location_id', 'grade', 'field', 'value', 'changedate'];
		
		public $timestamps = false;
		
	}
