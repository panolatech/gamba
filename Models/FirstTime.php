<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class FirstTime extends Model {
		
		protected $table = 'users';
		
	    /**
	     * The attributes that are mass assignable.
	     *
	     * @var array
	     */
		protected $fillable = ['id', 'first_login_token', 'password', 'updated_at'];
		
		public $timestamps = false;
		
	}
