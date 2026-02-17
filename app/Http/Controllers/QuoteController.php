<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProposalQuote;
use App\Quote;
use GuzzleHttp\Promise\Create;


class QuoteController extends Controller
{

   public function index()
   {
        $quote = Quote::orderBy('id','DESC')->get();
        return view('proposal.quote')->with(compact('quote'));
   }


//    public function Create()
//    {
//         $proposal_id = 0;
//         return view('proposal.create_quote',compact('proposal_id'));
//    }

   public function show($id)
   {
        $proposal_id = $id;
        return view('proposal.create_quote',compact('proposal_id'));
   }

   public function store(Request $request)
    {

        try {
            $multiplayer = $request->input('multiplayer');
            $rate = $request->input('rate');            
            $amount = $request->input('amount');
            $proposal_id = $request->input('proposal_id');
            $period = $request->input('period');
            $number_month = $request->input('number_month');
            $admin_fee = $amount*$multiplayer;


            if($multiplayer < 1.1 || $multiplayer > 1.42 ){
                $output = ['success' => false,
                    'msg' => 'The Multiplayer is out of range',
                ];
            }elseif($rate< 1 ||$rate >50){
                $output = ['success' => false,
                'msg' => 'The rate is out of range',
                 ];
            }elseif($number_month< 1 ||$number_month >12){
                $output = ['success' => false,
                'msg' => 'The number months is out of range',
                    ];
            }else{
                Quote::create([
                    'proposal_quote_id' => $proposal_id,
                    'multiplayer' => $multiplayer,
                    'rate' => $rate,
                    'amount' => $amount,
                    'admin_fee' => $admin_fee,
                    'number_month' => $number_month,
                    'period' => $period
                ]);
                
                $output = ['success' => true,
                        'msg' => 'agregad correctamente',
                ];
            }

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
                // 'msg' => __('messages.something_went_wrong'),
            ];
        }
        
        return redirect('quota/'.$proposal_id)->with('status', $output);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        try {
            $quote = Quote::find($id);

            $quote->delete();
            $output = ['success' => true,
                'msg' => __('quote.deleted_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
        return $output;        
    }

    public function edit($id)
    {
        $quote = Quote::find($id);
        $proposal = ProposalQuote::all();
        return view('proposal.update_quote')->with(compact('quote','proposal'));
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->only(['proposal_id', 'multiplayer', 'number_month', 'period', 'rate', 'amount', 'admin_fee']);
            $multiplayer = $request->input('multiplayer');
            $rate = $request->input('rate');
            $number_month = $request->input('number_month');
            $proposal_id = $input['proposal_id'];

            if($multiplayer < 1.1 || $multiplayer > 1.42 ){
                $output = ['success' => false,
                    'msg' => 'The Multiplayer is out of range',
                ];
            }elseif($rate< 1 ||$rate >50){
                $output = ['success' => false,
                'msg' => 'The rate is out of range',
                 ];
            }elseif($number_month< 1 ||$number_month >12){
                $output = ['success' => false,
                'msg' => 'The number month is out of range',
                    ];
            }else{
                $quote = Quote::find($id);
                $quote->multiplayer = $input['multiplayer'];
                $quote->rate = $input['rate'];
                $quote->amount = $input['amount'];
                $quote->admin_fee = $input['amount'] * $input['multiplayer'];
                $quote->number_month = $input['number_month'];
                $quote->period = $input['period'];
                $quote->save();

                $output = ['success' => true,
                'msg' => 'agregad correctamente',
            ];
            }
            
            
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
            'msg' => 'Error: '.$e->getLine().'Message:'.$e->getMessage(),
                // 'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('quota/'.$proposal_id)->with('status', $output);        
    }

    public function quota($id)
    {
        $proposal_id = $id;
        $quote = Quote::where('proposal_quote_id',$id)->orderBy('id','DESC')->get();
        return view('proposal.quote')->with(compact('quote','proposal_id'));
    }
}