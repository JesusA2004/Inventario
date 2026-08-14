<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Empresa</th>
            <th>Sucursal</th>
            <th>Estatus</th>
            <th>Esperados</th>
            <th>Encontrados</th>
            <th>No encontrados</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($audits as $audit)
            <tr>
                <td>{{ $audit->name }}</td>
                <td>{{ $audit->company?->name }}</td>
                <td>{{ $audit->branch?->name }}</td>
                <td>{{ $audit->status->label() }}</td>
                <td>{{ $audit->items->count() }}</td>
                <td>{{ $audit->items->where('status', \App\Enums\AuditItemStatus::Encontrado)->count() }}</td>
                <td>{{ $audit->items->where('status', \App\Enums\AuditItemStatus::NoEncontrado)->count() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
