<?php

use App\Models\Pista;
use App\Models\Reserva;
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
                    if ($reserva->user_id == auth()->id()) {
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

    public function updatePista()
    {
        $this->refresh();
    }

    public function reservar($fecha)
    {
        $this->validate([
            'pista_id' => 'required|exists:pistas,id',
        ]);

        if (Reserva::where('pista_id', $this->pista_id)->where('fecha_hora', $fecha)->exists()) {
            return redirect()->back()->with('error', 'La pista ya está reservada para esa fecha y hora.');
        }

        Reserva::create([
            'user_id' => auth()->id(),
            'pista_id' => $this->pista_id,
            'fecha_hora' => $fecha,
        ]);
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
    @error('error')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <select class="form-select mb-3" wire:model.live="pista_id">
        @foreach ($this->pistas as $pista)
            <option value="{{ $pista->id }}">{{ $pista->nombre }}</option>
        @endforeach
    </select>

    <button class="btn btn-sm btn-secondary mb-3" wire:click="$refresh">Actualizar</button>

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
                        $fecha = now()->startOfWeek()->addHours($h)->addDays($d)->format('Y-m-d H:i');
                        @endphp
                        <td>
                            @if ($this->tablero[$fecha] === 'O')
                                <span class="text-danger">Ocupado</span>
                            @elseif ($this->tablero[$fecha])
                                <button
                                    class="btn btn-sm btn-warning"
                                    wire:click="anularReserva({{ $this->tablero[$fecha] }})"
                                >
                                    Anular
                                </button>
                            @else
                                <button
                                    class="btn btn-sm btn-success"
                                    wire:click="reservar('{{ $fecha }}')"
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
