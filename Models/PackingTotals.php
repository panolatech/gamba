<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class PackingTotals extends Model {
		
		protected $table = 'packingtotals';
		
		protected $fillable = ['term', 'part', 'packing_id', 'camp', 'grade', 'theme', 'total', 'converted_total', 'location_totals', 'created'];
		
		public $timestamps = false;
		
	}
