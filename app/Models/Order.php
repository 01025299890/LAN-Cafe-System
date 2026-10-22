<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // protected $guarded = [];
    protected $table = 'orders';
    protected $fillable = [
        'user_id',
        'table_number',
         'status', 
         'total_amount',
          'notes'
          ];

          protected $casts = [
              'total_amount' => 'decimal:2',
          ];

          public function user()
          {
              return $this->belongsTo(User::class);
          }

            public function Items()
            {
                return $this->hasMany(OrderItem::class);
            }

}
