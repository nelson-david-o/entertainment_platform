<?php

namespace App\Http\Controllers;

use App\Events\AgreementEvent;
use App\Events\AgreementEventAdmin;
use App\Models\Agreement;
use App\Models\Buildings;
use App\Models\Calls;
use App\Models\Clients;
use App\Models\Llamada;
use App\Models\Movements;
use App\Models\Permisos;
use App\Models\User;
use App\Models\llamadas;
use App\Models\Notification;
use App\Notifications\Notify;
use Carbon\Carbon;
use Illuminate\Database\Query\orderBy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationSend;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia; 

class LlamadasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        return Inertia::render('Empleado/Index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function agreement($id)
    { 

        $clienteUser=User::where('id','=',$id)->get();
 
        $cliente=DB::select("SELECT * FROM `clients` 
                             INNER JOIN users 
                             ON clients.user_id = users.id  
                             WHERE user_id=".$id);  

        if ($debito=DB::select("SELECT * FROM movements  WHERE  `user_id`=".$id." LIMIT 1"))
        {
            $all=DB::select("SELECT * FROM movements WHERE  `user_id`=".$id);

            $debito=DB::select("SELECT SUM(valor_movement) AS 'debito' FROM movements 
                                WHERE  type_movement_id=1 AND `user_id`=".$id);
            $credito=DB::select("SELECT SUM(valor_movement) AS 'credito' FROM movements 
                                WHERE  type_movement_id=2 AND `user_id`=".$id);
            $cuenta=$debito[0]->debito-$credito[0]->credito;
            
        }
        else
        {  
            $cuenta=0;
            $all=0;
        }
            
        $acuerdos=DB::select('SELECT * FROM agreements where user_id='.$id);

        $id_building=DB::select('SELECT building_id FROM clients where user_id='.$id);
        $conjunto=DB::select('SELECT * FROM buildings where id_building='.$id_building[0]->building_id);

        $estado=$cliente[0]->state_id;

        $acuerdo_actual=DB::select('SELECT description FROM state where id_state='.$estado);
        
        $conjuntoNombre=$conjunto[0]->name_building;     
  

        return Inertia::render('Empleado/AcuerdoCuenta/AgreementAccount',[
             'conjunto' => $conjunto[0],
             'cliente' => $cliente[0],
             'cuenta' => $cuenta,
             'acuerdo' => $acuerdo_actual,
             'acuerdos' => $acuerdos,
             'movimientos' => $all,
             'photo' => $clienteUser[0]->profile_photo_url,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function cargarClientes(Request $request)
    {   
        $buscar=$request->buscar;
        // $users_cliente=User::role('Cliente')
        //                 ->where('name', 'like', '%'.$buscar.'%')
        //                 ->get();

        $users_cliente=DB::select("SELECT id,name,email,client_code,contract_number,state_id,user_id,building_id,description
            FROM clients 
            INNER JOIN state
            on state.id_state=clients.state_id
            INNER JOIN users
            on users.id=clients.user_id
            WHERE  users.name like '%".$buscar."%'" );

        return response()->json($users_cliente);
    }

    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchCall(Request $request)
    {   
        $id=$request->id;
        $call = Calls::select('*') 
                    ->where('id_call',$id) 
                    ->get();
        return response()->json($call);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVentana(Request $request)
    {   
        return Inertia::render('Empleado/VentanaIndex');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    { 
        $empleado = Auth::id();
        
        if ($debito=DB::select("SELECT * FROM movements  WHERE  `user_id`=".$id." LIMIT 1"))
        {
            $debito=DB::select("SELECT SUM(valor_movement) AS 'debito' FROM movements 
                                WHERE  type_movement_id=1 AND `user_id`=".$id);
            $credito=DB::select("SELECT SUM(valor_movement) AS 'credito' FROM movements 
                                WHERE  type_movement_id=2 AND `user_id`=".$id);
            $saldo=1;
        }
        else
        { 
            $saldo=0;
        } 


        if ($saldo===0)
        { 
            return Redirect::route('llamadas.agreement',['id' => $id]);
        }
        else
        {
            $clienteinfo=User::where('id','=',$id)->get();
            $cliente=DB::select("SELECT * FROM `clients` 
                                 INNER JOIN users 
                                 ON clients.user_id = users.id  
                                 WHERE user_id=".$id);  

            $llamadas=DB::select("SELECT id_call,client_id,name_call,phone_call,users.name as 'employee_id',description,state_id,calls.created_at,calls.updated_at FROM calls 
                INNER JOIN users
                on calls.employee_id = users.id  WHERE client_id=".$id);
            
            $id_building=DB::select('SELECT building_id FROM clients where user_id='.$id);
            $conjunto=DB::select('SELECT * FROM buildings where id_building='.$id_building[0]->building_id);

            $estado=$cliente[0]->state_id;  

            $acuerdo_actual=DB::select('SELECT description FROM state where id_state='.$estado);
            
            $conjuntoNombre=$conjunto[0]->name_building;  



            $cuentaTotal=$debito[0]->debito-$credito[0]->credito;
            
            return Inertia::render('Empleado/Llamada',[
                'empleadoid' => $empleado, 
                'conjunto' => $conjuntoNombre, 
                'cliente' => $clienteinfo[0], 
                'acuerdo' => $acuerdo_actual, 
                'llamadas' => $llamadas, 
                'cuentaTotal' => $cuentaTotal, 
                'admin' => $conjunto[0]->valor_administracion, 
            ]);
        }

        

    }



    public function client($id)
    {
        $empleado = Auth::id();
        $user=User::find($id); 
         
        
        return Inertia::render('Empleado/AcuerdoCuenta/Client', [
            'cliente' => $user, 
            'empleadoid' => $empleado,  
        ]);

    }

    public function account($id)
    {
        $empleado = Auth::id();
        $user=User::find($id); 
        $extracto=Permisos::extracto($id);
         
        
        return Inertia::render('Empleado/AcuerdoCuenta/StateAccount', [
            'cliente' => $user, 
            'empleadoid' => $empleado,  
            'extracto' => $extracto,  
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ventanaCreate($id)
    {
        
        $user= User::find($id); 
        
        return Inertia::render('Empleado/Llamada', [
            'cliente' => $user, 
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'cliente'=>'required',
            'nombre'=>'required',
            'telefono'=>'required',
            'idempleado'=>'required',
            'descripcion'=>'required',
            'estado'=>'required',

        ]);

        $call= Calls::create([  
            'client_id'=>$request->cliente,
            'name_call'=>$request->nombre,
            'phone_call'=>$request->telefono,
            'employee_id'=>$request->idempleado,
            'description'=>$request->descripcion,
            'state_id'=>$request->estado,
        ]); 
  
    }

    public function storeAgreement(Request $request)
    {

        $this->validate($request, [
            'cliente'=>'required',
            'nombre'=>'required',
            'telefono'=>'required',
            'idempleado'=>'required',
            'descripcion'=>'required',
            'estado'=>'required',
            'deuda_actual'=>'required',
            'cuotas'=>'required',
            'abono'=>'required',
            'observaciones'=>'required',

        ]);

        $call= Calls::create([  
            'client_id'=>$request->cliente,
            'name_call'=>$request->nombre,
            'phone_call'=>$request->telefono,
            'employee_id'=>$request->idempleado,
            'description'=>$request->descripcion,
            'state_id'=>$request->estado,
        ]); 
        
        $agreement=Agreement::create([   
            'user_id'=>$request->cliente,
            'employee_id'=>$request->idempleado,
            'current_debt'=>$request->deuda_actual,
            'credit'=>$request->abono,
            'quotas'=>$request->cuotas,
            'observations'=>$request->observaciones,
        ]); 
        $user=User::select('id','name','email')->find(Auth::user()->id)->first();
        $client=Clients::select('users.id','users.name','clients.building_id','buildings.name_building')
        ->join('users','clients.user_id','=','users.id')
        ->join('buildings', 'clients.building_id', '=', 'buildings.id_building')
        ->where('users.id','=',$request->cliente)->get();
        $notificaction=Notification::create([
            'from'=>$user,
            'action'=>'hizo',
            'notificable'=>'acuerdo',
            'info'=>$client[0]
        ]);
    
            return $notificaction;
  
    }
    public function sendEmails(Request $request)
    {
    
        $id =$request->notification;
        $when = Carbon::now()->addSecond(10);
        $notifications = Notification::where('id','=',$id)->where('sent',0)->get();  
        $info=json_decode($notifications[0]['info']);   
        $conjunto=Buildings::where('id_building','=',$info->building_id)->get();
        broadcast(new AgreementEvent($notifications[0]))->toOthers();
        broadcast(new AgreementEventAdmin($notifications[0],$conjunto[0]))->toOthers();
        $admin = User::select('id','name','email')->role('Admin')->get();
        $admins = User::select('users.*')->where('users.id','=',$conjunto[0]->administrator_id)->get();
        NotificationSend::send($admins, (new Notify($notifications[0]))->delay($when));
        NotificationSend::send($admin, (new Notify($notifications[0]))->delay($when));
        Notification::where('id','=',$id)->update(['sent'=> true]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeAccount(Request $request)
    {
        $id_building=DB::select('SELECT building_id FROM clients where user_id='.$request->cliente_id);
        $conjunto=DB::select('SELECT * FROM buildings where id_building='.$id_building[0]->building_id);

        $this->validate($request,[
            'cliente_id'=>'required',
            'capital_deuda'=>'required',
            'intereses'=>'required',
        ]);

        $call= Movements::create([
            'user_id'=>$request->cliente_id, 
            'type_movement_id'=>1,
            'valor_movement'=>$request->capital_deuda,
            'description_movement'=>'Saldo inicial', 
        ]);

        $call= Movements::create([
            'user_id'=>$request->cliente_id, 
            'type_movement_id'=>1,
            'valor_movement'=>$request->intereses, 
            'description_movement'=>'Intereses', 
        ]); 

        $gastos_cobranzas=(($request->capital_deuda+$request->intereses)*$conjunto[0]->gastos_cobranzas)/100;
        
        $call= Movements::create([
            'user_id'=>$request->cliente_id, 
            'type_movement_id'=>1,
            'valor_movement'=>$gastos_cobranzas, 
            'description_movement'=>'Gastos cobranzas', 
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function test($value='')
    {
        Permisos::roles();
    }
}
