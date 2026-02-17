<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Quote extends Model
{
    use HasFactory;
    protected $fillable = ['id','proposal_quote_id','multiplayer','rate','amount','admin_fee','number_month','period'];


    public function proposalQuote()
    {
        return $this->belongsTo(ProposalQuote::class);
    }


}