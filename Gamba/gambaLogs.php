<?php
	namespace App\Gamba;

	use Illuminate\Support\Facades\Session;

	use App\Gamba\gambaTerm;
	use App\Gamba\gambaUsers;

	use App\Models\ActionLog;

	class gambaLogs {

		public static function action_start_log($action, $description) {
			$time = date("Y-m-d h:i:s");
			$id = ActionLog::insertGetId([
				'action' => $action,
				'description' => $description,
				'start_time' => $time
			]);

			return $id;
		}

		public static function action_end_log($id) {
			$end_time = date("Y-m-d h:i:s");
			$end_timestamp = strtotime($end_time);
			$row = ActionLog::select('start_time')->where('id', $id)->first();
			$start_timestamp = strtotime($row['start_time']);
			$time_length = $end_timestamp - $start_timestamp;
			$update = ActionLog::where('id', $id)->update([
				'end_time' => $end_time,
				'time_length' => $time_length
			]);
		}

		public static function data_log($log_data, $logfile = 'convert.log', $show_date = "true", $double_return = NULL, $no_return = NULL, $selective_log = NULL, $log_this = NULL) {
			if($selective_log == NULL || ($selective_log == "true" && $log_this == "true")) {
				$log_path = config('gamba.log_path');
				$filename = $log_path . $logfile;
				$date = date("m-d H:i:s");
				$data = $log_data;
				if($show_date == "true") { $data .= " $date"; }
				if($no_return == "") { $data .= "\n\r"; }
				if($double_return == "true") {
					$data .= "\r\r";
				}
				file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
			}
		}

		public static function truncate_log($logfile = 'convert.log', $show_date = "true") {
			$log_path = config('gamba.log_path');
			$filename = $log_path . $logfile;
			$handle = fopen($filename, 'w+');
			$date = date("m-d H:i:s");
			if($show_date == "true") {
				$log_truncated = "Log Truncated $date\r";
			} else {
				$log_truncated = "\r";
			}
			fwrite($handle, $log_truncated);
			fclose($handle);
		}

		public static function admin_log_files($execute = "packingtotals") {
			$url = url('/');
			$content_array['side_nav'] = gambaNavigation::settings_nav();
			$content_array['page_title'] = "Log Files";
			$date = date("Ymdhis");
			$current_term = gambaTerm::year_by_status('C');
			$array = array(
				"quantityshort" => array(
					"title" => "Quanity Short",
					"logfiles" => array(
						"qtyshort.log",
					),
				),

				"packingtotals" => array(
					"title" => "Packing Total and Quanity Short",
					"logfiles" => array(
						"packingtotal.log",
						"qtyshort.log",
					)
				)
			);
			$content_array['content'] .= <<<EOT
		<dl class="sub-nav">
			<dt>Nav:</dt>
			<dd><a href="{$url}/settings/calc_packing_totals?term={$current_term}">Packing Total and Quanity Short</a></dd>
			<dd><a href="{$url}/settings/calc_quantity_short">Quantity Short</a></dd>
		</dl>
EOT;
			$content_array['content'] .= "<h3>".$array[$execute]['title']."</h3>";
			$i=1;
			foreach($array[$execute]['logfiles'] as $key => $value) {
				// Located in Routes/logs.php and LogsController@enroll_calc_log
				$content_array['content'] .= <<<EOT

			<script type="text/javascript">
				$(document).ready(function() {
					function functionToLoadFile(){
						jQuery.get('{$url}/enroll_calc_log?logfile={$value}&limit=5000&{$date}', function(data) {
							var logfile = data;
							$("#logfile{$i}").html("<p><a href='{$url}/logs/{$value}' target='logfile{$i}'>Log File</a></p><pre>" + logfile + "</pre>");
							setTimeout(functionToLoadFile, 500);
						});
					};
					setTimeout(functionToLoadFile, 10);
				});
			</script>
			<div id="logfile{$i}"></div>
EOT;
				$i++;
			}
			return $content_array;
		}

		public static function enroll_calc_log($array) {
			$log_path = config('gamba.log_path');
			header("Content-Type:text/plain");
			$log_file_name = $array['logfile'];
			$limit = $array['limit'];
			$logfile = $log_path . $log_file_name;
			if($limit == "") { $limit = "-c1k"; } else { $limit = "-c$limit"; }
			$cmd = "tail $limit $logfile";
			exec("$cmd 2>&1", $output);
			foreach($output as $outputline) {
				echo ("$outputline\n");
			}
		}
	}
