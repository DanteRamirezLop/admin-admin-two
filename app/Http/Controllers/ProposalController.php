<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProposalQuote;
use App\Quote;
use App\Utils\Util;
use Yajra\DataTables\Facades\DataTables;
use App\BusinessLocation;
use App\Mail\Proposal;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class ProposalController extends Controller
{
    public function index()
   {
  
    if (! auth()->user()->can('unit.view') && ! auth()->user()->can('unit.create')) {
        abort(403, 'Unauthorized action.');
    }
    $business_id = request()->session()->get('user.business_id');
    $business_locations = BusinessLocation::forDropdown($business_id);
    $proposal = ProposalQuote::orderBy('id','DESC')->get();
    // if (request()->ajax()) {
    //     $business_id = request()->session()->get('user.business_id');
        // return Datatables::of($unit)
        //     ->addColumn(
        //         'action',
        //         '@can("unit.update")
        //         <button data-href="{{action(\'App\Http\Controllers\UnitController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_unit_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
        //             &nbsp;
        //         @endcan
        //         @can("unit.delete")
        //             <button data-href="{{action(\'App\Http\Controllers\UnitController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_unit_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
        //         @endcan'
        //     )
        //     ->editColumn('allow_decimal', function ($row) {
        //         if ($row->allow_decimal) {
        //             return __('messages.yes');
        //         } else {
        //             return __('messages.no');
        //         }
        //     })
        //     ->editColumn('actual_name', function ($row) {
        //         if (! empty($row->base_unit_id)) {
        //             return  $row->actual_name.' ('.(float) $row->base_unit_multiplier.$row->base_unit->short_name.')';
        //         }

        //         return  $row->actual_name;
        //     })
        //     ->removeColumn('id')
        //     ->rawColumns(['action'])
        //     ->make(true);
    // }

    return view('proposal.index')->with(compact('proposal','business_locations'));
   }

   public function create()
    {
        $date = Carbon::now();
        $year = $date->format('dd/mm/YYYY');
        $business_id = request()->session()->get('user.business_id');
        $content = 'The quotes below have been customized to fit your needs based on our conversations about your business and the information you have provided. Please select the quote that best suits your business, and we will reserve that amount for you. If you have any questions, please do not hesitate to contact us. These quotes are valid for 30 days.';
        $note = 'Prior to funding, all deals will require photo id, voided check, landlord verification, and UCC clearance, if any. Offers over $25,000 will also require inspection and merchant review. Offer contingent on stipulations.';
        //Service staff filter
        // $service_staffs = User::all();
        return view('proposal.create')->with(compact('date','content','note'));       
    }

    public function store(Request $request)
    {       
        try {
			$business_id = request()->session()->get('user.business_id');
            $owner = $request->input('owner');
            $date = $request->input('date');            
            $content = $request->input('content');
            $note = $request->input('note');
            $email = $request->input('email');
            ProposalQuote::create([
				'business_id' => $business_id,
                'owner' => $owner,
                'date' => $date,
                'content' => $content,
                'note' => $note,
                'email' => $email
            ]);
            
            $output = ['success' => true,
                    'msg' => 'agregad correctamente',
                ];
            
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
                // 'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('proposals')->with('status', $output);
    }

    public function edit($id)
    {
        $date = Carbon::now();
        $year = $date->format('dd/mm/aaaa'); 
        $proposals = ProposalQuote::find($id);
        return view('proposal.edit')->with(compact('proposals','year'));
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
        try {
            $input = $request->only(['owner', 'date', 'content', 'note', 'email']);
            $proposal = ProposalQuote::find($id);
            $proposal->date = $input['date'];
            $proposal->owner = $input['owner'];
            $proposal->content = $input['content'];
            $proposal->note = $input['note'];
            $proposal->email = $input['email'];

            $proposal->save();
            
            $output = ['success' => true,
                    'msg' => 'agregad correctamente',
                ];
            
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
                // 'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('proposals')->with('status', $output);        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        try {
            $proposal = ProposalQuote::find($id);

            $proposal->delete();
                $output = ['success' => true,
                    'msg' => __('proforma.deleted_success'),
                ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
        return $output;        
    }

    public function pdf(Request $request)
    {
        $id = $request->id;
        
        $proposal = ProposalQuote::find($id);
        // $diaActual = Carbon::now()->isoFormat('dddd D \,\ Y');
        $fecha = Carbon::parse($proposal->date);
        $date = $fecha->isoFormat('MMMM D\, Y'); 
       
        $quotes = Quote::where('proposal_quote_id', $id)->get();
 
        $pdf = Pdf::set_option('isRemoteEnabled', true)->loadView('proposal.pdf',compact('proposal','quotes','date'));

       return $pdf->download('proposal.pdf');
    }

    public function send(Request $request)
    {        
        $id = $request->id;    
        $proposal = ProposalQuote::find($id); 
        $fecha = Carbon::parse($proposal->date);
        $date = $fecha->isoFormat('MMMM D\, Y'); 
        $quotes = Quote::where('proposal_quote_id', $id)->get();

        $data["email"] = $proposal->email;
        $data["title"] = "From ItSolutionStuff.com";
        $data["body"] = "This is Demo";
        $data["client_name"]=$proposal->owner;
        $data["subject"]=$request->get("subject");

        $correo = new Proposal($proposal,$quotes,$date);
        $pdf = Pdf::loadView('proposal.pdf',compact('proposal','quotes','date'));
        try {
            // Mail::send('proposal.mail', $data, function($message)use($data,$pdf) {
            //     $message->to($data["email"], $data["client_name"])
            //     ->subject($data["subject"])
            //     ->attachData($pdf->output(), "proposal.pdf");
            //     });

            // Mail::send('emails.mail', $data, function($message)use($data,$pdf) {
            //     $message->to('danterldk@gmail.com')
            //     ->subject($data["subject"])
            //     ->attachData($pdf->output(), "proposal.pdf");
            //     });

            Mail::to($proposal->email)
            ->send($correo,function($message)use($data,$pdf){
                $message->to($data["email"])
                ->subject($data["subject"])
                ->attachData($pdf->output(), "proposal.pdf");
            });

                // Mail::to($proposal->email)->send($correo,function($message)use($data,$pdf){
                //     $message->to($data["email"], $data["client_name"])
                //     ->subject($data["subject"])
                //     ->attachData($pdf->output(), 'proposal.pdf', ['mime' => 'application/pdf']);
                // });

            // Mail::send('proposal.pdf', $data, function($message){
            //     $message->to($data["email"], $data["client_name"])
            //     ->subject($data["title"]);
            //     $message->attachData($pdf);
            //     });

            return response()->json(['status' => true, 'msg' => "Tu mensaje fue enviado con exito"]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => "Ocurrió un error!!, intentalo más tarde"]);
        }
 
    }

}
