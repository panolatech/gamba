<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Locations extends Model {
		
		protected $table = 'locations';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'location', 'abbreviation', 'camp', 'term_data', 'cut_off_day'];
		
		public $timestamps = false;
		
	}
