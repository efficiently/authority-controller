<h1>Show Project</h1>

<p>{{ link_to_route('ac_projects.index', 'Return to all demandes') }}</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Priority</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>{{{ $acProject->name }}}</td>
            <td>{{{ $acProject->priority }}}</td>
            <td>{{ link_to_route('ac_projects.edit', 'Edit', $acProject->id) }}</td>
            <td>
                {{ Form::open(array('method' => 'DELETE', 'route' => ['ac_projects.destroy', $acProject->id])) }}
                    {{ Form::submit('Delete') }}
                {{ Form::close() }}
            </td>
        </tr>
    </tbody>
</table>
