<?php

namespace App\Http\Middleware;

use App\Models\Database_users;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ChangeDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $id = Auth::user()->id;
        // $cache = Cache::get($id);
        $id = $request->auth->users_id ?? $request->auth->id;
        $database = Database_users::where('id_users', $id)->first();
        // dd($database);
        // Config::set("database.connections.mysql", [
        // 'driver' => 'mysql',
        // "host" => env('DB_HOST'),
        // "database" => $database->nama,
        // "username" => $database->username,
        // "password" => $database->password,
        // "port" => '39157'
        // ]);
        Config::set("database.connections.mysql", [
            'driver' => 'mysql',
            "host" => '192.168.31.131',
            // "host" => '192.168.31.131',
            "database" => $database->nama,
            "username" => '49157',
            "password" => '49157-M@rsDB**70',
            "port" => '39157'
        ]);
        // dd('test');
        DB::purge('mysql');

        return $next($request);
    }
}
