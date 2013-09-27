<h1>Edit Project</h1>

{{ Form::model($acProject, ['route' => ['ac_projects.update', $acProject->id], 'method' => 'PUT']) }}

    {{ Form::text('name') }}

    {{ Form::text('priority') }}

    {{ Form::submit('Update') }}
	{{ link_to_route('ac_projects.show', 'Cancel', $acProject->id) }}
{{ Form::close() }}
