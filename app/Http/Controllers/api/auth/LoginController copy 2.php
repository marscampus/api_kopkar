<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use App\Models\Database_users;
use App\Models\Expired_tokens;
use App\Models\Karyawan;
use App\Models\Otp;
use App\Models\Menu_role;
use App\Models\Role;
use App\Models\User;
use App\Models\User_roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController22 extends Controller
{
    function login(Request $request){

        $user = null;
        if($request->aslogin == 'user'){
            $user =  $this->_loginUser($request);
        }elseif($request->aslogin == 'staff'){
            $user =  $this->_loginStaff($request);
        }

        if($user == null){
            return response()->json(['error'=>'something error'],500);
        }

        return $user;
    }

    function _loginUser($request){
        $user = User::where('email',$request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
          return response()->json(['error'=>'email atau password anda salah']);
        }
        if($user->status == 'denied'){
            return response()->json(['error'=>'akses ditolak']);
        }
        if($user->status != 'active'){
            return response()->json(['error'=>'otp']);
        }

        if($user->role == 'master'){
            $menuBase = Menu_role::where('nama_app','99d74a27-f7e5-4aa4-be6a-6d8308b65e1f')
            ->where('priority','=','admin')
            ->first();

            #jika menu tidak ada atau belum diisi error
            if(!$menuBase){
                return  response()->json(['error'=>'akses ditolak']);
            }
            $user_role_master = User_roles::where("users_id",$user->id);
            $saveRoleMaster = $user_role_master->update([
                'menu'=>$menuBase->menu
            ]);
            # jika menu tidak tersimpan maka error
            if(!$saveRoleMaster){
                return response()->json(['error'=>'akses ditolak']);
            }
        }

        if($user->role == 'client'){
            $databaseUser = Database_users::where('id_users',$user->id);
            if(!$databaseUser->exists()){
                return  response()->json(['error'=>"user tidak ditemukan"]);
            }
            $database = $databaseUser->first();
            if(now() > $database->used_until_date){
                return  response()->json(['error'=>"masa sewa aplikasi habis"]);
            }

            $menuBase = Menu_role::where('nama_app','99d74a27-f7e5-4aa4-be6a-6d8308b65e1f')
            ->where('priority','!=','admin')
            ->first();

            #jika menu tidak ada atau belum diisi error
            if(!$menuBase){
                return  response()->json(['error'=>'akses ditolak']);
            }

            $userRoles = User_roles::updateOrCreate(
                [
                    'users_id' => $user->id,
                    'nama_app_id' => '99d74a27-f7e5-4aa4-be6a-6d8308b65e1f'
                ],
                [
                    'menu' => $menuBase->menu
                ]
            );

            # jika menu tidak tersimpan maka error
            if(!$userRoles){
                return response()->json(['error'=>'akses ditolak']);
            }
        }
        $menu = User_roles::where('users_id',$user->id)
        ->where('nama_app_id','99d74a27-f7e5-4aa4-be6a-6d8308b65e1f')
        ->first();
        if(!$menu){
            return response()->json(['error'=>'akses ditolak']);
        }

        $jsonString = $menu->menu;

        $menuArray = json_decode($jsonString);

        #create token
        $token = $user->createToken($user->name);

        $expired_tokens = Expired_tokens::create([
            'id_personal_tokens'=>$token->accessToken->id,
            'token'=> $token->plainTextToken,
            'expired_at'=>now()->addHours(6)
        ]);

        return response()->json(['name'=>$user->name,'email'=>$user->email,'role'=>'[]','token'=>$token->plainTextToken,'menu'=>$menuArray]);
    }

    function _loginStaff($request){
        $user = Karyawan::where('email',$request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error'=>'email atau password anda salah']);
        }
        if($user->status == 'denied'){
            return response()->json(['error'=>'akses ditolak']);
        }

        $role = $user->ability;
        $string = str_replace("'", '"', $role);
        $tokenAbilities = json_decode($string);
        $token = $user->createToken($user->name,$tokenAbilities);

        $expired_tokens = Expired_tokens::create([
            'id_personal_tokens'=>$token->accessToken->id,
            'token'=> $token->plainTextToken,
            'expired_at'=>now()->addHours(6)
        ]);
        $menu = User_roles::where('users_id',$user->id)
        ->where('nama_app_id','99d74a27-f7e5-4aa4-be6a-6d8308b65e1f')
        ->first();
        if(!$menu){
            return response()->json(['error'=>'akses ditolak']);
        }
        $jsonString = $menu->menu;

        $menuArray = json_decode($jsonString);
        return response()->json(['name'=>$user->name,'email'=>$user->email,'role'=>$tokenAbilities,'token'=>$token->plainTextToken,'menu'=>$menuArray]);

    }

    function otp(Request $request){
        $validator = Validator::make($request->all(),[
            'otp'=>'required',
            'email'=>'required'
        ]);

        if($validator->fails()){
            return response(['error'=>$validator->errors()->all()],422);
        }

        $otp = Otp::where('email',$request->email)
        ->where('otp',$request->otp)
        ->where('email_verified','=',null)
        ->where('expired_at','>=',now())
        ->first();

        try {

            if($otp->exists()){
                $updateOtp = $otp->update([
                    'email_verified'=>now()
                ]);

                if(!$updateOtp){
                    return response()->json(['error'=>'terjadi kesalahan'],500);
                }
                $user = User::where('email',$otp->email);
                $update = $user->update([
                    'email_verified_at'=>now(),
                    'status'=>'active'
                ]);

                if($update > 0){
                    return response()->json(['status'=>'success']);
                }
                return response()->json(['error'=>'error'],500);
            }

            return response()->json(['error'=>'tidak ditemukan'],500);

        } catch (\Throwable $th) {
            return response()->json(['error'=>'tidak ditemukan'],500);
        }

    }
}
