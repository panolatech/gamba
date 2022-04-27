<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;

	use App\Models\Config;
	use App\Models\SQLLog;

	use App\Gamba\gambaUsers;
	
	class gambaDebug {
		
		public static function session() {
			$user_id = Session::get('uid');
			$debug = config('gamba.debug');
			if($user_id <= 2 && $debug == 1 && $user_id != "") {
				$content = <<<EOT
				<div class="panel-group" id="accordion">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
								<a class="accordion-toggle" data-toggle="collapse" href="#session">Returned Session Array</a>
							</h4>
						</div>
						<div id="session" class="panel-collapse collapse">
							<div class="panel-body">
EOT;
				$content .= var_dump($_SESSION);
				$content .= <<<EOT
							</div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
				$("#session").collapse("hide");
				</script>
EOT;
			}
			return $content;
		}
		
		public static function returns($return) {
			$user_id = Session::get('uid');
			$debug = config('gamba.debug');
			if($user_id <= 2 && $debug == 1 && is_array($return) && $user_id != "") {
				$content = <<<EOT
				<div class="panel-group" id="accordion">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
								<a class="accordion-toggle" data-toggle="collapse" href="#returns">Returned Data Array</a>
							</h4>
						</div>
						<div id="returns" class="panel-collapse collapse">
							<div class="panel-body">
EOT;
				$content .= var_dump($return);
				$content .= <<<EOT
							</div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
				$("#returns").collapse("hide");
				</script>
EOT;
			}
		}
		
		public static function preformatted_arrays($array, $id, $title, $debug_override = NULL) {
			$user_id = Session::get('uid');
			$debug = config('gamba.debug');
			if($user_id <= 2 && $debug == 1 && is_array($array) && $debug_override != "true" && $user_id != "") {
				$content = <<<EOT
				<ul class="accordion" data-accordion>
					<li class="accordion-navigation">
						<a href="#{$id}">{$title} Data Array</a>
						<div id="{$id}" class="content">
EOT;
				//ob_start();
				//var_dump($array);
				$content .= "<pre>" . print_r($array, true) . "</pre>";
				$content .= <<<EOT
						</div>
					</li>
				</ul>
EOT;
			}
			return $content;
		}
		
		public static function sql_log($class, $function, $sql, $error) {
			if($error != "") { 
				$query = new SQLLog;
				$query->class = $class;
				$query->function = $function;
				$query->sql = $sql;
				$query->error = $error;
				$query->created = date("Y-m-d H:i:s");
				$query->save();
			}
		}
		
		public static function debug_status() {
			$row = Config::select('value')->where('field', 'debug')->first();
			$debug_status = $row['value'];
			return $debug_status;
		}

		/*
		 * Disabled - Remove Later - 10/17/16
		public static function total_compare() {
			$row = Config::select('value')->where('field', 'total_compare')->first();
			$total_compare = $row['value'];
			return $total_compares;
		} */
		
		public static function itemtype_override() {
			$row = Config::select('value')->where('field', 'itemtype_override')->first();
			$itemtype_override = $row['value'];
			return $itemtype_override;
		}
		
		public static function cookie_login() {
			$row = Config::select('value')->where('field', 'cookie_login')->first();
			$cookie_login = $row['value'];
			return $cookie_login;
		}
		
		/**
		 * Alert Box
		 * @param unknown $message
		 * @param unknown $type (success, warning, info, alert, seconary)
		 */
		public static function alert_box($message, $type) {
			$content .= <<<EOT
			<div data-alert class="alert-box {$type} radius">
			{$message}
				<a href="#" class="close">&times;</a>
			</div>
EOT;
			return $content;
		}
		
		public static function array2ul($array) {
			$content .= "<table>";
			foreach ($array as $row) {
			   $content .= "<tr>";
			   foreach ($row as $column) {
			      $content .= "<td>$column</td>";
			   }
			   $content .= "</tr>";
			}    
			$content .= "</table>";
			return $content;
		}
		
	}
