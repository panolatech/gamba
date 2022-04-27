<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Gamba\gambaActivities;
use App\Gamba\gambaThemes;

class SettingsThemesController extends Controller
{

    public function index(Request $array)
    {
        $user_group = Session::get('group');
    	$content = gambaThemes::view_themes_activities($array['camp'], $array['term']);
    	if($user_group == 3) {
    	    return view('app.settings.cwthemesactivities', ['content' => $content]);
    	} else {
    	   return view('app.settings.home', ['content' => $content]);
    	}
    }

    public function formTheme(Request $array)
    {
        $user_group = Session::get('group');
        $content = gambaThemes::data_form_all_theme($array, $return);
        if($user_group == 3) {
            return view('app.settings.cwthemesactivities', ['content' => $content]);
        } else {
            return view('app.settings.home', ['content' => $content]);
        }
    }

    public function themeTypes(Request $array)
    {
    	$content = gambaThemes::theme_types_view($array, $return);
    	return view('app.settings.home', ['content' => $content]);
    }

    public function updateTheme(Request $array)
    {
		$url = url('/');
    	$result = gambaThemes::data_update_theme($array);
		return redirect("{$url}/settings/themes?camp={$array['camp']}&term={$array['term']}#theme{$array['theme_id']}");
    }

    public function theme_delete(Request $array)
    {
		$url = url('/');
    	$result = gambaThemes::data_delete_theme($array);
		return redirect("{$url}/settings/themes?camp={$array['camp']}&term={$array['term']}");
    }

    public function storeTheme(Request $array)
    {
		$url = url('/');
    	$result = gambaThemes::data_add_theme($array);
		return redirect("{$url}/settings/themes?term={$array['term']}&camp={$array['camp']}&r=$result#theme{$array['theme_id']}");
    }

    public function unlinkTheme(Request $array)
    {
		$url = url('/');
    	gambaThemes::data_unlink_theme($array);
		return redirect("{$url}/settings/themes?term=".$array['term']."&camp=".$array['camp']."#theme".$array['theme_id']);
    }

    public function updateThemeTypes(Request $array)
    {
    	$url = url('/');
    	$result = gambaThemes::data_update_theme_types($array);
		return redirect("{$url}/settings/theme_types?updated=1&r=$result");
    }

    public function formActivity(Request $array)
    {
        $user_group = Session::get('group');
        $content = gambaActivities::data_form_all_activity($array, $array['r']);
        if($user_group == 3) {
            return view('app.settings.cwthemesactivities', ['content' => $content]);
        } else {
            return view('app.settings.home', ['content' => $content]);
        }
    }

    public function updateActivity(Request $array)
    {
    	$url = url('/');
    	$return = gambaActivities::data_update_activity($array);
		return redirect("{$url}/settings/themes?camp=".$array['camp']."&term=".$array['term']."#activity".$return['id']);
    }

    public function storeActivity(Request $array)
    {
    	$url = url('/');
		$return = gambaActivities::data_add_activity($array);
		return redirect("{$url}/settings/themes?term=".$array['term']."&camp=".$array['camp']."#activity".$return['id']);
    }

    public function delete_activity(Request $array)
    {
    	$url = url('/');
		$return = gambaActivities::data_delete_activity($array);
		return redirect("{$url}/settings/themes?term=".$array['term']."&camp=".$array['camp']."&delete=1#theme".$array['theme_id']);
    }

}