document.addEventListener('DOMContentLoaded', () => {
    const userTableBody = document.getElementById('userTableBody');
    const userForm = document.getElementById('userForm');
    const saveUserButton = document.getElementById('saveUserButton');

    // Fetch and display users
    function fetchUsers() {
        fetch('/users')
            .then(response => response.json())
            .then(users => {
                userTableBody.innerHTML = '';
                users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${user.id}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>
                            <button class="edit-btn" data-id="${user.id}">Edit</button>
                            <button class="delete-btn" data-id="${user.id}">Delete</button>
                        </td>
                    `;
                    userTableBody.appendChild(row);
                });
            });
    }

    // Save user (create or update)
    saveUserButton.addEventListener('click', () => {
        const formData = new FormData(userForm);
        const userId = formData.get('id');
        const method = userId ? 'PUT' : 'POST';
        const url = userId ? `/users/${userId}` : '/users';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(() => {
            fetchUsers();
            userForm.reset();
        });
    });

    // Delete user
    userTableBody.addEventListener('click', (event) => {
        if (event.target.classList.contains('delete-btn')) {
            const userId = event.target.dataset.id;
            fetch(`/users/${userId}`, { method: 'DELETE' })
                .then(() => fetchUsers());
        }
    });

    // Edit user
    userTableBody.addEventListener('click', (event) => {
        if (event.target.classList.contains('edit-btn')) {
            const userId = event.target.dataset.id;
            fetch(`/users/${userId}`)
                .then(response => response.json())
                .then(user => {
                    Object.keys(user).forEach(key => {
                        const input = userForm.querySelector(`[name="${key}"]`);
                        if (input) input.value = user[key];
                    });
                });
        }
    });

    // Initial fetch
    fetchUsers();
});

const signupForm = document.getElementById('registration-form');
if (signupForm) {
    const existingFields = new Set();

    signupForm.querySelectorAll('input, select').forEach(field => {
        const fieldName = field.getAttribute('name');
        if (existingFields.has(fieldName)) {
            field.remove();
        } else {
            existingFields.add(fieldName);
        }
    });
}
