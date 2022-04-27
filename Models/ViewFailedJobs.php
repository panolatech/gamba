<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class ViewFailedJobs extends Model {

		protected $table = 'viewfailedjobs';

		protected $fillable = ['id', 'type', 'queue', 'payload', 'failed_at'];

		public $timestamps = false;

	}
