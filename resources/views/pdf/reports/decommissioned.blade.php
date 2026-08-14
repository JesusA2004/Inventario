<table>
    <thead>
        <tr>
            <th>Clave</th>
            <th>Dispositivo</th>
            <th>Empresa</th>
            <th>Sucursal</th>
            <th>Fecha de baja</th>
            <th>Motivo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($assets as $asset)
            <tr>
                <td>{{ $asset->internal_code }}</td>
                <td>{{ $asset->name }}</td>
                <td>{{ $asset->company?->name }}</td>
                <td>{{ $asset->branch?->name }}</td>
                <td>{{ $asset->decommissioned_at?->format('d/m/Y') }}</td>
                <td>{{ $asset->decommission_reason }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
