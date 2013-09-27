<h1>Edit Task</h1>
{{ Form::model($acTask, ['route' => ['ac_projects.ac_tasks.update', $acTask->ac_project_id, $acTask->id], 'method' => 'PUT']) }}
    {{ Form::text('name') }}

    {{ Form::submit('Update') }}
    {{ link_to_route('ac_projects.ac_tasks.show', 'Cancel', [$acTask->ac_project_id, $acTask->id]) }}
{{ Form::close() }}
