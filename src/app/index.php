<?php

use Lib\PPIcons\{Delete, LoaderCircle, Pencil, Plus, X};

use Lib\Prisma\Classes\Prisma;

$prisma = Prisma::getInstance();

$todos = $prisma->todo->findMany([
    'orderBy' => [
        'createdAt' => 'asc'
    ]
]);

function createTodo($data)
{
    $prisma = Prisma::getInstance();
    $newTodo = $prisma->todo->create([
        'data' => [
            'title' => $data->title,
            'completed' => false
        ]
    ]);

    if ($newTodo) {
        return [
            'message' => 'Todo created successfully',
            'todo' => $newTodo
        ];
    } else {
        return [
            'error' => true,
            'message' => 'Failed to create todo'
        ];
    }
}

function updateTodo($data)
{
    $prisma = Prisma::getInstance();
    $updatedTodo = $prisma->todo->update([
        'where' => [
            'id' => $data->id
        ],
        'data' => [
            'title' => $data->title,
            'completed' => $data->completed
        ]
    ]);

    if ($updatedTodo) {
        return [
            'message' => 'Todo updated successfully',
            'todo' => $updatedTodo
        ];
    } else {
        return [
            'error' => true,
            'message' => 'Failed to update todo'
        ];
    }
}

function deleteTodo($data)
{
    $prisma = Prisma::getInstance();
    $deletedTodo = $prisma->todo->delete([
        'where' => [
            'id' => $data->id
        ]
    ]);

    if ($deletedTodo) {
        return [
            'message' => 'Todo deleted successfully'
        ];
    } else {
        return [
            'error' => true,
            'message' => 'Failed to delete todo'
        ];
    }
}

function toggleCompleted($data)
{
    $prisma = Prisma::getInstance();
    $toggledTodo = $prisma->todo->update([
        'where' => [
            'id' => $data->id
        ],
        'data' => [
            'completed' => !$data->completed
        ]
    ]);

    if ($toggledTodo) {
        return [
            'message' => 'Todo updated successfully',
            'todo' => $toggledTodo
        ];
    } else {
        return [
            'error' => true,
            'message' => 'Failed to update todo'
        ];
    }
}

?>

<!-- Stunning To-Do List (HTML Only) -->
<div class="min-h-screen bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-5 bg-white/80 rounded-2xl shadow-2xl p-8 backdrop-blur-md border border-gray-200">
        <div class="flex flex-col items-center">
            <svg class="w-16 h-16 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">Stunning To-Do List</h2>
            <p class="mt-2 text-center text-sm text-gray-600">Stay productive in style ✨</p>
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex gap-2 items-center">
                <input name="search" type="text" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400 transition" placeholder="Search todos..." autocomplete="off" oninput="search = this.value" value="{{ search }}" />
                <button type="button" class="bg-gray-100 text-gray-500 px-3 py-2 rounded-lg font-semibold hover:bg-gray-200 transition" title="Clear search" onclick="setSearch('')">
                    <X class="size-5" />
                    <LoaderCircle class="size-5 animate-spin hidden" />
                </button>
            </div>
            <form class="flex gap-2" onsubmit="addTodo">
                <input name="title" type="text" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 transition" placeholder="Add a new task..." autocomplete="off" oninput="title = this.value" value="{{ title }}" />
                <button type="submit" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-3 py-2 rounded-lg font-semibold shadow-md hover:from-purple-600 hover:to-pink-600 transition">
                    <Plus class="size-5" />
                </button>
            </form>
            <div class="flex justify-center">
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    <button class="px-3 py-1.5 rounded-md text-sm font-medium transition bg-white text-purple-600 shadow">All</button>
                    <button class="px-3 py-1.5 rounded-md text-sm font-medium transition text-gray-500 hover:bg-white">Todo</button>
                    <button class="px-3 py-1.5 rounded-md text-sm font-medium transition text-gray-500 hover:bg-white">Done</button>
                </div>
            </div>
        </div>
        <ul class="space-y-3 h-44 overflow-y-auto">
            <template pp-for="todo in todos">
                <li class="flex items-center justify-between bg-white rounded-lg shadow p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center">
                        <input type="checkbox" class="mr-3 h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded" checked="{{todo.completed}}" onchange="toggleCompleted(todo)" />
                        <span class="text-gray-800 text-lg {{ todo.completed ? 'line-through text-gray-400' : '' }}" pp-bind="todo.title"></span>
                    </div>
                    <div class="flex gap-2 items-center">
                        <button class="text-blue-500 hover:text-blue-700" title="Edit" onclick="editTodo(todo)">
                            <Pencil class="size-5" />
                        </button>
                        <button class="text-red-500 hover:text-red-700" title="Delete" onclick="deleteTodo(todo.id)">
                            <Delete class="size-5" />
                        </button>
                    </div>
                </li>
            </template>
            <!-- More items here -->
        </ul>
        <div class="flex justify-between items-center mt-4 text-sm text-gray-400 border-t pt-3">
            <span>
                Showing <span class="font-bold">{{ todos.length }}</span> of
                <span class="font-bold">{{ todos.length }}</span>
            </span>
            <span>
                <span class="font-bold">{{ todos.filter(t => t.completed).length }}/{{ todos.length }}</span>
                Task completed
            </span>
        </div>
    </div>
</div>

<script>
    const jsonTodos = <?php echo json_encode($todos); ?>;

    const [todos, setTodos] = pphp.state(jsonTodos);
    const [title, setTitle] = pphp.state('');
    const [search, setSearch] = pphp.state('');

    pphp.effect(() => {
        console.log('search updated:', search);
        if (search.value.trim() === '') {
            setTodos(jsonTodos);
            return;
        }
        const filtered = todos.filter(todo => todo.title.toLowerCase().includes(search.value.toLowerCase()));
        setTodos(filtered);
    }, [search]);

    export const addTodo = async (form) => {
        const titleValue = form.data.title;
        if (!titleValue.trim()) return;

        const newTodo = await pphp.fetchFunction('createTodo', {
            title: titleValue
        });

        if (newTodo.error) {
            console.error(newTodo.message);
            return;
        }

        const updated = [...todos, newTodo.response.todo];
        setTodos(updated);
        setTitle('');
    }

    export const editTodo = async (todo) => {
        const newTitle = prompt('Edit todo title:', todo.title);
        if (newTitle === null || newTitle.trim() === '') return;

        const updatedTodo = await pphp.fetchFunction('updateTodo', {
            id: todo.id,
            title: newTitle.trim(),
            completed: todo.completed
        });

        if (updatedTodo.error) {
            console.error(updatedTodo.message);
            return;
        }

        const updated = todos.map(t => {
            if (t.id === todo.id) {
                return {
                    ...t,
                    title: updatedTodo.response.todo.title
                };
            }
            return t;
        });

        setTodos(updated);
    }

    export const deleteTodo = async (id) => {
        if (!confirm('Are you sure you want to delete this todo?')) return;

        if (!id) {
            console.error('Todo ID is required for deletion');
            return;
        }

        const deleted = await pphp.fetchFunction('deleteTodo', {
            id
        });

        if (deleted.error) {
            console.error(deleted.message);
            return;
        }

        const updated = todos.filter(todo => todo.id !== id);
        setTodos(updated);
    }

    export const toggleCompleted = async (todo) => {
        await pphp.fetchFunction('toggleCompleted', {
            ...todo
        });

        const updated = todos.map(t => {
            if (t.id === todo.id) {
                return {
                    ...t,
                    completed: !t.completed
                };
            }
            return t;
        });

        setTodos(updated);
    }
</script>