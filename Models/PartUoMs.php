<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model as Eloquent;

	class PartUoMs extends Eloquent {
		
		protected $table = 'partuoms';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'name', 'code', 'active', 'date_added', 'date_updated'];
		
		public $timestamps = false;
		
	}
