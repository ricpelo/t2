<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $fillable = ['fecha_hora', 'pista_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function pista()
    {
        return $this->belongsTo(Pista::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
