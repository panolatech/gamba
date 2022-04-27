<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class ActionLog extends Model {

		protected $table = 'actionlog';

		protected $primaryKey = 'id';

		protected $fillable = ['id', 'action', 'description', 'start_time', 'end_time', 'time_length'];

		public $timestamps = false;

	}
