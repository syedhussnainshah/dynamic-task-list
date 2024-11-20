<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <!-- Task Creation Form -->
        <div class="row">
            <form id="create-task-form" method="POST" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="name" class="form-control" placeholder="Task Name" required minlength="5" maxlength="255">
                </div>
                <div class="col-md-4">
                    <input type="text" name="description" class="form-control" placeholder="Task Description">
                </div>
                <div class="col-md-3">
                    <input type="datetime-local" name="deadline" id="deadline-input" class="form-control" min="" required>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary w-100">Create Task</button>
                </div>
            </form>
        </div>

        <!-- Task List Table -->
        <div class="row mt-4">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Task Name</th>
                            <th>Task Description</th>
                            <th>Deadline</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="taskListRow">
                        @forelse($tasks as $task)
                        <input type="hidden" value="{{ $task->deadline }}" id="deadline-{{ $task->id }}">
                        <tr id="task-{{ $task->id }}">
                            <td>{{ $task->name }}</td>
                            <td>{{ $task->description }}</td>
                            <td id="countdown-{{ $task->id }}"></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="editTask(`{{ $task->id }}`)">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTask(`{{ $task->id }}`)">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr id="noTaskFound">
                            <td colspan="4" class="text-center">No tasks found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editId = null;
        const deadlineInput = document.getElementById('deadline-input');
        const intervals = {};
        console.log(intervals);

        function setMinDateTime() {
            const now = new Date();
            const formattedDateTime = now.toISOString().slice(0, 16);
            deadlineInput.min = formattedDateTime;
        }

        // Initialize on page load
        setMinDateTime();

        // Optional: Update the min value dynamically (e.g., if the form is open for a while)
        setInterval(setMinDateTime, 3000);

        const tasks = @json($tasks);

        // Initialize countdowns for existing tasks
        tasks.forEach(task => startCountdown(task.deadline, task.id));

        /**
         * Starts a countdown for the given deadline and task ID.
         */
        function startCountdown(deadline, taskId) {
            const deadlineDate = new Date(deadline);
            const countdownElement = document.getElementById(`countdown-${taskId}`);
            let intervalId;

            function updateCountdown() {
                const now = new Date();
                const timeLeft = deadlineDate - now;

                if (timeLeft <= 0) {
                    countdownElement.textContent = "Task expired";
                    clearInterval(intervalId);
                } else {
                    const hours = String(Math.floor(timeLeft / (1000 * 60 * 60))).padStart(2, '0');
                    const minutes = String(Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                    const seconds = String(Math.floor((timeLeft % (1000 * 60)) / 1000)).padStart(2, '0');
                    countdownElement.textContent = `${hours}:${minutes}:${seconds} remaining`;
                }
            }

            updateCountdown();
            intervalId = setInterval(updateCountdown, 1000);

            intervals[taskId] = intervalId;
        }

        /**
         * Handle task creation form submission
         */
        document.getElementById('create-task-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            if (editId) {
                formData.append('_method', 'PUT');
                fetch(`/tasks/${editId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.ok ? response.json() : Promise.reject(response))
                    .then(task => {
                        // Clear the previous countdown interval for the updated task
                        clearInterval(intervals[editId]);
                        intervals[editId] = null;

                        // Update the task row
                        updateTaskRow(task);
                        editId = null;
                        document.getElementById('noTaskFound')?.remove();
                        this.reset();
                    })
                    .catch(error => console.error('Error updating task:', error));
                return;
            }

            fetch("{{ route('tasks.store') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.ok ? response.json() : Promise.reject(response))
                .then(task => {
                    addTaskRow(task);
                    document.getElementById('noTaskFound')?.remove();
                    this.reset();
                })
                .catch(error => console.error('Error creating task:', error));
        });

        /**
         * Adds a new task row to the task list.
         */
        function addTaskRow(task) {
            const taskListRow = document.getElementById('taskListRow');
            const taskRow = document.createElement('tr');
            taskRow.id = `task-${task.id}`;
            taskRow.innerHTML = `
                <td>${task.name}</td>
                <td>${task.description}</td>
                <td id="countdown-${task.id}"></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editTask(${task.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteTask(${task.id})">Delete</button>
                </td>
                <input type="hidden" value="${task.deadline}" id="deadline-${task.id}">
            `;
            taskListRow.appendChild(taskRow);
            startCountdown(task.deadline, task.id);
        }

        function updateTaskRow(task) {
            const taskRow = document.getElementById(`task-${task.id}`);
            taskRow.children[0].textContent = task.name;
            taskRow.children[1].textContent = task.description;
            taskRow.children[2].textContent = task.deadline;

            // Start a new countdown and clear previous one
            clearInterval(intervals[task.id]);
            startCountdown(task.deadline, task.id);
        }

        /**
         * Handle task deletion
         */
        function deleteTask(taskId) {
            fetch(`/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to delete task');
                    document.getElementById(`task-${taskId}`)?.remove();

                    if (!document.getElementById('taskListRow').children.length) {
                        document.getElementById('taskListRow').innerHTML = `
                        <tr id="noTaskFound">
                            <td colspan="4" class="text-center">No tasks found</td>
                        </tr>`;
                    }
                })
                .catch(error => console.error('Error deleting task:', error));
        }

        function editTask(taskId) {
            const taskRow = document.getElementById(`task-${taskId}`);
            const taskName = taskRow.children[0].textContent;
            const taskDescription = taskRow.children[1].textContent;
            const deadline = document.getElementById(`deadline-${taskId}`).value;

            document.getElementById('create-task-form').name.value = taskName;
            document.getElementById('create-task-form').description.value = taskDescription;
            document.getElementById('create-task-form').deadline.value = deadline;

            document.getElementById('create-task-form').querySelector('button[type="submit"]').textContent = 'Update Task';
            editId = taskId;

            document.getElementById('create-task-form').scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>
