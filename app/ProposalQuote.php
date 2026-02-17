<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalQuote extends Model
{
    use HasFactory;
    protected $table = 'proposal_quotes';
    protected $fillable = ['id','business_id','owner','date','content','note','reference','email'];

    public function quote()
    {
       return $this->hasMany(Quote::class);
    }
}