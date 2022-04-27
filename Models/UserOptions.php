<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class UserOptions extends Model {
		
		protected $table = 'useroptions';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'user_id', 'option_key', 'option_values'];
		
		public $timestamps = false;
		
	}
