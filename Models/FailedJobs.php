<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class FailedJobs extends Model {

		protected $table = 'failed_jobs';

		protected $primaryKey = 'id';

		protected $fillable = ['id', 'connection', 'queue', 'payload', 'failed_at'];

		public $timestamps = false;

	}
