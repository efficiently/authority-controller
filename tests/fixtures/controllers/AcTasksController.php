<?php

class AcTasksController extends AcBaseController
{

    /**
     * Parent resource AcProject Repository
     *
     * @var AcProject
     */
    protected $acProjectModel;

    /**
     * AcTask Repository
     *
     * @var AcTask
     */
    protected $acTaskModel;

    /**
     * Parent resource AcProject instance
     *
     * @var AcProject
     */
    protected $acProject;

    /**
     * current AcTask instance
     *
     * @var AcTask
     */
    protected $acTask;

    /**
     * AcTask collection
     *
     * @var Illuminate\Database\Eloquent\Collection
     */
    protected $acTasks;

    public function __construct(AcProject $acProjectModel, AcTask $acTaskModel)
    {
        $this->acProjectModel = $acProjectModel;
        $this->acTaskModel = $acTaskModel;
        $this->loadAndAuthorizeResource('ac_project');
        $this->loadAndAuthorizeResource('ac_task', ['through' => 'ac_project']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  int  $acProjectId
     * @return Response
     */
    public function index($acProjectId)
    {
        // $this->acTasks = $this->acTaskModel->all();

        return view('ac_tasks.index', compact_property($this, 'acProject', 'acTasks'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  int  $acProjectId
     * @return Response
     */
    public function create($acProjectId)
    {
        return view('ac_tasks.create', compact_property($this, 'acProject'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  int  $acProjectId
     * @return Response
     */
    public function store($acProjectId)
    {
        // $this->acTask = App::make('AcTask');

        $this->acTask->fill(Input::except('_method', '_token'));
        $this->acTask->ac_project_id = $acProjectId;
        if ($this->acTask->save()) {
            return Redirect::route('ac_projects.ac_tasks.index', $acProjectId);
        } else {
            return Redirect::route('ac_projects.ac_tasks.create', $acProjectId)
                ->withErrors($this->acTask->errors())
                ->with('message', 'There were validation errors.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $acProjectId
     * @param  int  $id
     * @return Response
     */
    public function show($acProjectId, $id)
    {
        // $this->acTask = $this->acTaskModel->findOrFail($id);

        return view('ac_tasks.show', compact_property($this, 'acTask'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $acProjectId
     * @param  int  $id
     * @return Response
     */
    public function edit($acProjectId, $id)
    {
        // $this->acTask = $this->acTaskModel->find($id);

        if (is_null($this->acTask)) {
            return Redirect::route('ac_projects.ac_tasks.index', $acProjectId);
        }

        return view('ac_tasks.edit', compact_property($this, 'acTask'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $acProjectId
     * @param  int  $id
     * @return Response
     */
    public function update($acProjectId, $id)
    {
        // $this->acTask = $this->acTaskModel->find($id);

        $this->acTask->fill(Input::except('_method', '_token'));
        if ($this->acTask->save()) {
            return Redirect::route('ac_projects.ac_tasks.show', [$acProjectId, $id]);
        } else {
            return Redirect::route('ac_projects.ac_tasks.edit', $id)
            ->withErrors($this->acTask->errors())
            ->with('message', 'There were validation errors.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $acProjectId
     * @param  int  $id
     * @return Response
     */
    public function destroy($acProjectId, $id)
    {
        // $this->acTaskModel->find($id)->delete();

        $this->acTask->delete();

        return Redirect::route('ac_projects.ac_tasks.index', $acProjectId);
    }
}
