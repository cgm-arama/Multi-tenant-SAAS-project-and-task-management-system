// FILE: /public/assets/js/kanban.js
// SplashProjects - Kanban Board Drag and Drop

let draggedTask = null;

// Initialize drag and drop
document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
});

function initializeDragAndDrop() {
    const taskCards = document.querySelectorAll('.task-card');
    const columnTasks = document.querySelectorAll('.column-tasks');

    taskCards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    columnTasks.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragenter', handleDragEnter);
        column.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    draggedTask = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.column-tasks').forEach(column => {
        column.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    this.classList.remove('drag-over');

    if (draggedTask) {
        const taskId = draggedTask.dataset.taskId;
        const newColumnId = this.dataset.columnId;
        const oldColumnId = draggedTask.closest('.column-tasks').dataset.columnId;

        // Move the task visually
        this.appendChild(draggedTask);

        // Update on server if column changed
        if (newColumnId !== oldColumnId) {
            moveTaskToColumn(taskId, newColumnId);
        }
    }

    return false;
}

function moveTaskToColumn(taskId, columnId) {
    const data = {
        column_id: columnId,
        position: 0
    };

    fetch(BASE_URL + '/tasks/' + taskId + '/move', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            // Reload page to restore correct state
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to move task');
        location.reload();
    });
}

// Show create task modal
function showCreateTaskModal() {
    showModal('createTaskModal');
}

// Create task
function createTask(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const data = {
        csrf_token: formData.get('csrf_token'),
        column_id: formData.get('column_id'),
        title: formData.get('title'),
        description: formData.get('description'),
        assigned_to: formData.get('assigned_to'),
        priority: formData.get('priority'),
        due_date: formData.get('due_date')
    };

    fetch(BASE_URL + '/tasks/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.error || 'Failed to create task'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create task');
    });
}

// Show add column modal
function showAddColumnModal() {
    const columnName = prompt('Enter column name:');
    if (columnName && columnName.trim()) {
        addColumn(columnName.trim());
    }
}

// Add column
function addColumn(name) {
    const urlParams = new URLSearchParams(window.location.search);
    const boardId = BOARD_ID || window.location.pathname.split('/').pop();

    const formData = new FormData();
    formData.append('name', name);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    fetch(BASE_URL + '/boards/' + boardId + '/addColumn', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to add column');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add column');
    });
}
