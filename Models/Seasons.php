<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class Seasons extends Model {

		protected $table = 'seasons';

		protected $primaryKey = 'year';

		protected $fillable = ['year', 'status', 'json_array', 'created_at', 'updated_at'];

		public $timestamps = true;

	}
