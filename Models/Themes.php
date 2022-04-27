<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Themes extends Model {
		
		protected $table = 'themes';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'name', 'term', 'camp_type', 'theme_type', 'cg_staff', 'link_id', 'minor', 'quantity_id', 'theme_options', 'budget'];
		
		public $timestamps = false;
		
	}
