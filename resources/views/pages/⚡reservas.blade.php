<?php

use App\Models\Pista;
use App\Models\Reserva;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $pista_id;

    public function mount()
    {
        $this->pista_id = Pista::first()->id;
    }

    #[Computed]
    public function pistas()
    {
        return Pista::all();
    }

    #[Computed]
    public function tablero()
    {
        $tablero = [];

        $lunes = now()->startOfWeek();

        for ($h = 10; $h < 20; $h++) {
            for ($d = 0; $d < 5; $d++) {
                $fecha = $lunes->copy()->addHours($h)->addDays($d)->format('Y-m-d H:i');
                $reserva = Reserva::where('pista_id', $this->pista_id)
                    ->where('fecha_hora', $fecha)
                    ->first();
                if ($reserva) {
                    if ($reserva->user_id == Auth::id()) {
                        $tablero[$fecha] = $reserva->id;
                    } else {
                        $tablero[$fecha] = 'O';
                    }
                } else {
                    $tablero[$fecha] = null;
                }
            }
        }

        return $tablero;
    }

    public function reservar($fecha)
    {
        $this->validate([
            'pista_id' => 'required|exists:pistas,id'
        ]);

        try {
            $fecha = Carbon::createFromFormat('Y-m-d H:i', $fecha);

            if ($fecha->lessThan(now())) {
                $this->addError('fecha', 'No puedes reservar para una fecha pasada.');
                return;
            }

            if ($fecha->hour < 10
                || $fecha->hour > 20
                || $fecha->dayOfWeek > 5
                || $fecha->dayOfWeek == 0
                || $fecha->weekOfYear != now()->weekOfYear
            ) {
                $this->addError('fecha', 'Solo puedes reservar de lunes a viernes de esta semana entre las 10:00 y las 20:00.');
                return;
            }

            if (Reserva::where('pista_id', $this->pista_id)->where('fecha_hora', $fecha)->exists()) {
                $this->addError('pista', 'La pista ya está reservada para esa fecha y hora.');
                return;
            }

            Reserva::create([
                'user_id' => Auth::id(),
                'pista_id' => $this->pista_id,
                'fecha_hora' => $fecha,
            ]);
        } catch (InvalidFormatException $e) {
            $this->addError('fecha', 'Formato de fecha incorrecto.');
        }
    }

    public function anularReserva($reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        Gate::authorize('anular-reserva', $reserva);

        $reserva->delete();
    }
};
?>

<div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <select class="form-select mb-3" wire:model.live="pista_id">
        @foreach ($this->pistas as $pista)
            <option value="{{ $pista->id }}">{{ $pista->nombre }}</option>
        @endforeach
    </select>

    <table class="table">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Lunes</th>
                <th>Martes</th>
                <th>Miércoles</th>
                <th>Jueves</th>
                <th>Viernes</th>
            </tr>
        </thead>
        <tbody>
            @for ($h = 10; $h < 20; $h++)
                <tr>
                    <td>{{ $h }}:00</td>
                    @for ($d = 0; $d < 5; $d++)
                        @php
                        $fecha = now()->startOfWeek()->addHours($h)->addDays($d);
                        $fechaStr = $fecha->format('Y-m-d H:i');
                        @endphp
                        <td>
                            @if ($this->tablero[$fechaStr] === 'O')
                                <span class="text-danger">Ocupado</span>
                            @elseif ($this->tablero[$fechaStr] !== null)
                                <button
                                    class="btn btn-sm btn-warning"
                                    wire:click="anularReserva({{ $this->tablero[$fechaStr] }})"
                                >
                                    Anular
                                </button>
                            @elseif ($fecha->lessThan(now()))
                                <span class="text-muted">Pasado</span>
                            @else
                                <button
                                    class="btn btn-sm btn-success"
                                    wire:click="reservar('{{ $fechaStr }}')"
                                >
                                    Reservar
                                </button>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
