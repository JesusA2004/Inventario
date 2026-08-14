<table>
    <thead>
        <tr>
            <th>Clave</th>
            <th>Dispositivo</th>
            <th>Empresa</th>
            <th>Sucursal</th>
            <th>Área</th>
            <th>Responsable</th>
            <th>Estatus</th>
            <th>Alta</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($assets as $asset)
            <tr>
                <td>{{ $asset->internal_code }}</td>
                <td>{{ $asset->name }}</td>
                <td>{{ $asset->company?->name }}</td>
                <td>{{ $asset->branch?->name }}</td>
                <td>{{ $asset->department?->name }}</td>
                <td>{{ $asset->currentResponsible?->full_name }}</td>
                <td>{{ $asset->status->label() }}</td>
                <td>{{ $asset->acquired_at?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
