<?php

namespace RAD\Streams\Http\Controllers;

use Exception;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RAD\Streams\Http\Controllers\Traits\DatabaseUpdate;
use RAD\Streams\Models\DataType;
use RAD\Streams\Models\Permission;
use RAD\Streams\Streams;

class StreamsDatabaseController extends Controller
{
    use AppNamespaceDetectorTrait;
    use DatabaseUpdate;

    public function index()
    {
        Streams::can('browse_database');

        return view('streams::tools.database.index');
    }

    public function create()
    {
        Streams::can('browse_database');

        return view('streams::tools.database.edit-add');
    }

    public function store(Request $request)
    {
        Streams::can('browse_database');

        $tableName = $request->name;

        try {
            Schema::create($tableName, function (Blueprint $table) use ($request) {
                foreach ($this->buildQuery($request) as $query) {
                    $query($table);
                }
            });

            if (isset($request->create_model) && $request->create_model == 'on') {
                $params = [
                    'name' => ucfirst($tableName),
                ];

                if (in_array('deleted_at', $request->input('field.*'))) {
                    $params['--softdelete'] = true;
                }

                Artisan::call('streams:make:model', $params);
            }

            return redirect()
                ->route('streams.database.index')
                ->with(
                    [
                        'message'    => "Successfully created $tableName table",
                        'alert-type' => 'success',
                    ]
                );
        } catch (Exception $e) {
            return back()->with(
                [
                    'message'    => 'Exception: '.$e->getMessage(),
                    'alert-type' => 'error',
                ]
            );
        }
    }

    public function edit($table)
    {
        Streams::can('browse_database');

        $rows = $this->describeTable($table);

        return view('streams::tools.database.edit-add', compact('table', 'rows'));
    }

    /**
     * Update database table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        Streams::can('browse_database');

        $this->renameTable($request->original_name, $request->name);
        $this->renameColumns($request, $request->name);
        $this->dropColumns($request, $request->name);
        $this->updateColumns($request, $request->name);

        return redirect()
            ->route('streams.database.index')
            ->with(
                [
                    'message'    => "Successfully updated $request->name table",
                    'alert-type' => 'success',
                ]
            );
    }

    public function reorder_column(Request $request)
    {
        Streams::can('browse_database');

        if ($request->ajax()) {
            $table = $request->table;
            $column = $request->column;
            $after = $request->after;
            if ($after == null) {
                // SET COLUMN TO THE TOP
                DB::query("ALTER $table MyTable CHANGE COLUMN $column FIRST");
            }

            return 1;
        }

        return 0;
    }

    public function show($table)
    {
        Streams::can('browse_database');

        return response()->json($this->describeTable($table));
    }

    public function destroy($table)
    {
        Streams::can('browse_database');

        try {
            Schema::drop($table);

            return redirect()
                ->route('streams.database.index')
                ->with(
                    [
                        'message'    => "Successfully deleted $table table",
                        'alert-type' => 'success',
                    ]
                );
        } catch (Exception $e) {
            return back()->with(
                [
                    'message'    => 'Exception: '.$e->getMessage(),
                    'alert-type' => 'error',
                ]
            );
        }
    }

    /********** BREAD METHODS **********/

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function addBread(Request $request)
    {
        Streams::can('browse_database');

        $table = $request->input('table');

        return view('streams::tools.database.edit-add-bread', $this->prepopulateBreadInfo($table));
    }

    private function prepopulateBreadInfo($table)
    {
        $displayName = Str::singular(implode(' ', explode('_', Str::title($table))));

        return [
            'table'                 => $table,
            'slug'                  => Str::slug($table),
            'display_name'          => $displayName,
            'display_name_plural'   => Str::plural($displayName),
            'model_name'            => $this->getAppNamespace().Str::studly(Str::singular($table)),
            'generate_permissions'  => true,
        ];
    }

    public function storeBread(Request $request)
    {
        Streams::can('browse_database');

        $dataType = new DataType();
        $data = $dataType->updateDataType($request->all())
            ? [
                'message'    => 'Successfully created new BREAD',
                'alert-type' => 'success',
            ]
            : [
                'message'    => 'Sorry it appears there may have been a problem creating this bread',
                'alert-type' => 'error',
            ];

        return redirect()->route('streams.database.index')->with($data);
    }

    public function addEditBread($id)
    {
        Streams::can('browse_database');

        return view(
            'streams::tools.database.edit-add-bread', [
            'dataType' => DataType::find($id),
        ]
        );
    }

    public function updateBread(Request $request, $id)
    {
        Streams::can('browse_database');

        /** @var \RAD\Streams\Models\DataType $dataType */
        $dataType = DataType::find($id);
        $data = $dataType->updateDataType($request->all())
            ? [
                'message'    => "Successfully updated the {$dataType->name} BREAD",
                'alert-type' => 'success',
            ]
            : [
                'message'    => 'Sorry it appears there may have been a problem updating this bread',
                'alert-type' => 'error',
            ];

        return redirect()->route('streams.database.index')->with($data);
    }

    public function deleteBread($id)
    {
        Streams::can('browse_database');

        /** @var \RAD\Streams\Models\DataType $dataType */
        $dataType = DataType::find($id);
        $data = DataType::destroy($id)
            ? [
                'message'    => "Successfully removed BREAD from {$dataType->name}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => 'Sorry it appears there was a problem removing this bread',
                'alert-type' => 'danger',
            ];

        Permission::removeFrom($dataType->name);

        return redirect()->route('streams.database.index')->with($data);
    }
}
