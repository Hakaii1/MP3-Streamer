// USER MANAGEMENT TAB FUNCTIONALITY

let pendingAddUser = null;

async function loadAuthorizedUsers() {
    try {
        const response = await fetch('api_users.php?action=list');
        const data = await response.json();

        if (data.success) {
            renderAuthorizedUsers(data.users);
            document.getElementById('authorizedUserCount').textContent =
                `(${data.users.length} user${data.users.length !== 1 ? 's' : ''})`;
        } else {
            showUsersError('Failed to load users: ' + data.error);
        }
    } catch (error) {
        showUsersError('Network error: ' + error.message);
    }
}

function renderAuthorizedUsers(users) {
    const container = document.getElementById('authorizedUsersList');

    if (users.length === 0) {
        container.innerHTML = `
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fa-solid fa-user-slash text-4xl mb-3"></i>
                <p>No authorized users yet</p>
                <p class="text-sm mt-1">Click "Add User" to grant access</p>
            </div>
        `;
        return;
    }

    container.innerHTML = users.map(user => {
        const fullName = user.fullname || 'Unknown User';
        const safeName = escapeHtml(fullName);

        return `
            <div class="grid grid-cols-[1fr_2fr_100px] gap-4 px-6 py-4 hover:bg-[#1a1a1a] transition items-center">
                <span class="text-pink-custom font-mono">${escapeHtml(user.BiometricsID)}</span>
                <span class="text-white">${safeName}</span>
                <div class="text-right">
                    <button onclick="removeAuthorizedUser('${escapeHtml(user.BiometricsID)}', '${safeName}')" 
                        class="text-red-400 hover:text-red-300 transition p-2" title="Remove Access">
                        <i class="fa-solid fa-user-minus"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Add User Modal Functions
function openAddUserModal() {
    document.getElementById('addUserBiometricsInput').value = '';
    document.getElementById('addUserNameDisplay').value = '';
    document.getElementById('addUserStatus').classList.add('hidden');
    document.getElementById('confirmAddUserBtn').disabled = true;
    pendingAddUser = null;

    document.getElementById('addUserModal').classList.remove('hidden');
    document.getElementById('addUserModal').classList.add('flex');

    // Focus on input
    setTimeout(() => document.getElementById('addUserBiometricsInput').focus(), 100);
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserModal').classList.remove('flex');
    pendingAddUser = null;
}

async function lookupUser() {
    const biometricsId = document.getElementById('addUserBiometricsInput').value.trim();
    const statusDiv = document.getElementById('addUserStatus');
    const nameInput = document.getElementById('addUserNameDisplay');
    const addBtn = document.getElementById('confirmAddUserBtn');

    if (!biometricsId) {
        statusDiv.innerHTML = '<span class="text-yellow-400"><i class="fa-solid fa-exclamation-circle mr-1"></i>Please enter a BiometricsID</span>';
        statusDiv.classList.remove('hidden');
        return;
    }

    // Show loading
    statusDiv.innerHTML = '<span class="text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Looking up user...</span>';
    statusDiv.classList.remove('hidden');
    nameInput.value = '';
    addBtn.disabled = true;
    pendingAddUser = null;

    try {
        const response = await fetch(`api_users.php?action=lookup&id=${encodeURIComponent(biometricsId)}`);
        const data = await response.json();

        if (data.success) {
            if (data.is_authorized) {
                statusDiv.innerHTML = '<span class="text-yellow-400"><i class="fa-solid fa-exclamation-triangle mr-1"></i>This user is already authorized</span>';
                nameInput.value = data.user.fullname || 'Unknown';
                addBtn.disabled = true;
            } else {
                statusDiv.innerHTML = '<span class="text-green-400"><i class="fa-solid fa-check-circle mr-1"></i>User found! Ready to add.</span>';
                nameInput.value = data.user.fullname || 'Unknown';
                nameInput.classList.remove('text-gray-400');
                nameInput.classList.add('text-white');
                addBtn.disabled = false;
                pendingAddUser = {
                    biometricsId: data.user.BiometricsID,
                    fullname: data.user.fullname
                };
            }
        } else {
            statusDiv.innerHTML = `<span class="text-red-400"><i class="fa-solid fa-times-circle mr-1"></i>${escapeHtml(data.error)}</span>`;
            nameInput.value = '';
            addBtn.disabled = true;
        }
    } catch (error) {
        statusDiv.innerHTML = '<span class="text-red-400"><i class="fa-solid fa-times-circle mr-1"></i>Network error</span>';
        addBtn.disabled = true;
    }
}

async function confirmAddUser() {
    if (!pendingAddUser) return;

    const statusDiv = document.getElementById('addUserStatus');
    const addBtn = document.getElementById('confirmAddUserBtn');

    addBtn.disabled = true;
    statusDiv.innerHTML = '<span class="text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Adding user...</span>';

    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('biometrics_id', pendingAddUser.biometricsId);

        const response = await fetch('api_users.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(`${pendingAddUser.fullname} has been granted access`, 'success');
            closeAddUserModal();
            loadAuthorizedUsers();
        } else {
            statusDiv.innerHTML = `<span class="text-red-400"><i class="fa-solid fa-times-circle mr-1"></i>${escapeHtml(data.error)}</span>`;
            addBtn.disabled = false;
        }
    } catch (error) {
        statusDiv.innerHTML = '<span class="text-red-400"><i class="fa-solid fa-times-circle mr-1"></i>Network error</span>';
        addBtn.disabled = false;
    }
}

async function removeAuthorizedUser(biometricsId, name) {
    if (!confirm(`Are you sure you want to remove access for ${name}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('biometrics_id', biometricsId);

        const response = await fetch('api_users.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(`${name}'s access has been removed`, 'success');
            loadAuthorizedUsers();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('Network error: ' + error.message, 'error');
    }
}

function showUsersError(message) {
    document.getElementById('authorizedUsersList').innerHTML = `
        <div class="px-6 py-8 text-center text-red-400">
            <i class="fa-solid fa-exclamation-triangle text-2xl mb-2"></i>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'fixed top-4 right-4 z-[70] space-y-2';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');

    const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

    toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3`;
    toast.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${escapeHtml(message)}</span>
    `;
    toast.style.animation = 'slideIn 0.3s ease';

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Allow Enter key to trigger lookup
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('addUserBiometricsInput');
    if (input) {
        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                lookupUser();
            }
        });
    }
});
