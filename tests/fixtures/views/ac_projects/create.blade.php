<h1>Create Project</h1>

{{ Form::open(['route' => 'ac_projects.store']) }}
    {{ Form::text('name') }}

    {{ Form::text('priority') }}

    {{ Form::submit('Submit') }}
{{ Form::close() }}
