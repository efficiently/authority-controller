<?php

class AcProjectsController extends AcBaseController
{

    /**
     * AcProject Repository
     *
     * @var AcProject
     */
    protected $acProjectModel;

    /**
     * current AcProject instance
     *
     * @var AcProject
     */
    protected $acProject;

    /**
     * AcProject collection
     *
     * @var Illuminate\Database\Eloquent\Collection
     */
    protected $acProjects;

    public function __construct(AcProject $acProjectModel = null)
    {
        $this->acProjectModel = $acProjectModel ?: new AcProject;
        $this->loadAndAuthorizeResource();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // $this->acProjects = $this->acProjectModel->all();

        return view('ac_projects.index', compact_property($this, 'acProjects'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('ac_projects.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        // $this->acProject = App::make('AcProject');

        $this->acProject->fill(Input::except('_method', '_token'));
        if ($this->acProject->save()) {
            return Redirect::route('ac_projects.index');
        } else {
            return Redirect::route('ac_projects.create')
                ->withErrors($this->acProject->errors())
                ->with('message', 'There were validation errors.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        // $this->acProject = $this->acProjectModel->findOrFail($id);

        return view('ac_projects.show', compact_property($this, 'acProject'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        // $this->acProject = $this->acProjectModel->find($id);

        if (is_null($this->acProject)) {
            return Redirect::route('ac_projects.index');
        }

        return view('ac_projects.edit', compact_property($this, 'acProject'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        // $this->acProject = $this->acProjectModel->find($id);

        $this->acProject->fill(Input::except('_method', '_token'));
        if ($this->acProject->save()) {
            return Redirect::route('ac_projects.show', $id);
        } else {
            return Redirect::route('ac_projects.edit', $id)
            ->withErrors($this->acProject->errors())
            ->with('message', 'There were validation errors.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        // $this->acProjectModel->find($id)->delete();

        $this->acProject->delete();

        return Redirect::route('ac_projects.index');
    }
}
