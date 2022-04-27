<?php
	namespace App\Gamba;
	
	use Illuminate\Support\Facades\Session;

	use App\Gamba\gambaUsers;
	
	class gambaEmails {
		
		public static function new_user_email($name, $email, $upass) {
			$url = url('/');
			$msg .= "
$name,

You have been given access to GAMBA, Galileo’s database for camp supplies.
To login to your account please click on this link:
{$url}/login/first_time?id=$upass

Your username is: $email

Once you login, you’ll be asked to create a password.  Please bookmark this
site on your browser so that you can access it at any time.  Thanks for your
work in making Galileo’s programs great for kids!

-The Galileo Warehouse Team
			";
			$mail_date = date("F j, Y");
			$subject = "Galileo Learning GAMBA - Supply Management System Account - $mail_date";
			$headers = 'From: warehouse@galileo-learning.com' . "\r\n" .
			'Reply-To: warehouse@galileo-learning.com' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
					mail($email, $subject, $msg, $headers, "-fwarehouse@galileo-learning.com");
		}
		
	}
