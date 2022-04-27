<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class ThemeLink extends Model {
		
		protected $table = 'themelink';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'term', 'camp_type'];
		
		public $timestamps = false;
		
	}
