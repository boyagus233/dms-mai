<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Transaction Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($documentUpload as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td>{{ $t->creator_name }}</td>
                <td>{{ $t->checker_name }}</td>
                <td>{{ $t->approver_name }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
