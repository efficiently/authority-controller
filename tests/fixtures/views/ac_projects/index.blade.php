<h1>All Projects</h1>

<p>{{ link_to_route('ac_projects.create', 'Add new project') }}</p>

@if ($acProjects->count())
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Priotity</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($acProjects as $acProject)
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
            @endforeach
        </tbody>
    </table>
@else
    There are no projects
@endif
