<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class ViewJobs extends Model {

		protected $table = 'viewjobs';

		protected $fillable = ['id', 'queue', 'payload', 'attempts', 'reserved', 'reserved_at', 'available_at', 'created_at'];

		public $timestamps = false;

	}
