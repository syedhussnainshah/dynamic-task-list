<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks.
     *
     * This method retrieves all tasks from the database and returns
     * the 'tasks.index' view with the tasks data.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $tasks = Task::all();
        return view('tasks.index', compact('tasks'));
    }

    /**
     * Store a newly created task in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
        ]);

        $task = Task::create($request->all());

        return response()->json($task, 201);
    }

    /**
     * Remove the specified task from storage.
     *
     * @param  int  $id  The ID of the task to be deleted.
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified task.
     *
     * @param  int  $id  The ID of the task to edit.
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $task = Task::findOrFail($id);
        return response()->json($task, 200);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'deadline' => 'required|date|after:now',
        ]);

        $task->update($validated);

        return response()->json($task, 200);
    }
}
