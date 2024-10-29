<?php

namespace App\Http\Controllers\api\plugin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class PluginController extends Controller
{
    function checkUserHadPlugin(Request $request) {
        try {

            $user = DB::table('users')->where('email', $request->email)->first();
            

            $plugin = DB::table('plugins_master as pm')
            ->leftJoin('invoices as in', 'in.apps_id', 'pm.id_plugin')
            ->leftJoin('plugins as p', 'p.id', 'pm.id_plugin')
            ->where('in.users_id', $user->id)
            ->where('pm.id_user', $user->id)
            ->where('p.status', '!=', 'nonactive') 
            ->whereNotIn('pm.status_plug', ['ban', 'uninstalled']) // Status plugin harus valid
            ->select('p.id','p.nama')
            ->distinct()
            ->get();

            return response()->json($plugin);
        } catch (\Exception $er) {
            return response()->json(['error'=> $er]);
        }
    }
}
