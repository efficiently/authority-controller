<h1>Show Task</h1>

<p>{{ link_to_route('ac_projects.ac_tasks.index', 'Return to all issues', $acTask->ac_project_id) }}</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>{{{ $acTask->name }}}</td>
            <td>{{ link_to_route('ac_projects.ac_tasks.edit', 'Edit', [$acTask->ac_project_id, $acTask->id]) }}</td>
            <td>
                {{ Form::open(['method' => 'DELETE', 'route' => ['ac_projects.ac_tasks.destroy', $acTask->ac_project_id, $acTask->id]]) }}
                    {{ Form::submit('Delete') }}
                {{ Form::close() }}
            </td>
        </tr>
    </tbody>
</table>
