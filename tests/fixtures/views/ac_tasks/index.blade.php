<h1>All Tasks</h1>

<p>{{ link_to_route('ac_projects.ac_tasks.create', 'Add new task', $acProject->id) }}</p>

@if ($acTasks->count())
	<table>
		<thead>
			<tr>
				<th>Name</th>
			</tr>
		</thead>

		<tbody>
			@foreach ($acTasks as $acTask)
                <tr>
					<td>{{{ $acTask->name }}}</td>
                    <td>{{ link_to_route('ac_projects.ac_tasks.edit', 'Edit', [$acTask->ac_project_id, $acTask->id]) }}</td>
                    <td>
                        {{ Form::open(['method' => 'DELETE', 'route' => ['ac_projects.ac_tasks.destroy', $acTask->ac_project_id, $acTask->id]]) }}
                            {{ Form::submit('Delete') }}
                        {{ Form::close() }}
                    </td>
				</tr>
			@endforeach
		</tbody>
	</table>
@else
	There are no tasks
@endif
