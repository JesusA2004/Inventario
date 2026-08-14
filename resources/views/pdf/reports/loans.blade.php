<table>
    <thead>
        <tr>
            <th>Activo</th>
            <th>Asignado a</th>
            <th>Salida</th>
            <th>Devolución esperada</th>
            <th>Devolución real</th>
            <th>Estatus</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($loans as $loan)
            <tr>
                <td>{{ $loan->asset?->internal_code }}</td>
                <td>{{ $loan->assignedTo?->full_name }}</td>
                <td>{{ $loan->loan_date?->format('d/m/Y') }}</td>
                <td>{{ $loan->expected_return_date?->format('d/m/Y') }}</td>
                <td>{{ $loan->actual_return_date?->format('d/m/Y') }}</td>
                <td>{{ $loan->status->label() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
