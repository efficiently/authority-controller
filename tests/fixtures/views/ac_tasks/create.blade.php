<h1>Create Task</h1>

{{ Form::open( ['route' => ['ac_projects.ac_tasks.store', $acProject->id]] ) }}
    {{ Form::text('name') }}

    {{ Form::submit('Submit') }}
{{ Form::close() }}
