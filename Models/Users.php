<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;

	class Users extends Model {
		
		protected $table = 'users';
		
		protected $primaryKey = 'id';
	    /**
	     * The attributes that are mass assignable.
	     *
	     * @var array
	     */
		protected $fillable = ['id', 'email', 'name', 'first_login_token', 'permission', 'created', 'last_login', 'last_activity', 'session_id', 'block', 'login', 'camp', 'locations', 'token', 'ip_address'];

	    /**
	     * The attributes that should be hidden for arrays.
	     *
	     * @var array
	     */
	    protected $hidden = [
	        'password', 'remember_token',
	    ];
		
		public $timestamps = false;
		
	}
