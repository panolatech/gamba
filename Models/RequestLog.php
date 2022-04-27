<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class RequestLog extends Model {
		
		protected $table = 'requestlog';
		
		protected $primaryKey = 'id';
		
		protected $fillable = ['id', 'user', 'acsid', 'editdate', 'action'];
		
		public $timestamps = false;
		
	}
