<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Reorders extends Model {
		
		protected $table = 'reorders';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'term', 'status', 'camp', 'created', 'updated', 'user', 'xmlstring', 'fishbowl_response'];
		
		public $timestamps = false;
		
	}
