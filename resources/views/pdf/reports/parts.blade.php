<table>
    <thead>
        <tr>
            <th>Clave</th>
            <th>Pieza</th>
            <th>Marca</th>
            <th>Estatus</th>
            <th>Cantidad</th>
            <th>Activo relacionado</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($parts as $part)
            <tr>
                <td>{{ $part->internal_code }}</td>
                <td>{{ $part->name }}</td>
                <td>{{ $part->brand?->name }}</td>
                <td>{{ $part->status->label() }}</td>
                <td>{{ $part->quantity }}</td>
                <td>{{ $part->relatedAsset?->internal_code }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
