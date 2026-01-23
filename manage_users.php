<?php
// manage_users.php
// Authorized Users Management Page

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Only IT department can access this page
if ($_SESSION['department'] !== 'Information Technology Department - LRN') {
    header('Location: host.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Authorized Users • MP3 Host</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="icon" type="image/x-icon" href="images/logo.jpg">
    <style>
        :root {
            --pink: #F8C8DC;
        }

        .text-pink-custom {
            color: var(--pink);
        }

        .bg-pink-custom {
            background-color: var(--pink);
        }

        .hover-text-pink:hover {
            color: var(--pink);
        }

        .border-pink-custom {
            border-color: var(--pink);
        }

        .custom-scroll {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }

        .custom-scroll:hover {
            scrollbar-color: #555 transparent;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: transparent;
            border-radius: 10px;
        }

        .custom-scroll:hover::-webkit-scrollbar-thumb {
            background-color: #555;
        }

        .user-row {
            transition: all 0.2s ease;
        }

        .user-row:hover {
            background: rgba(248, 200, 220, 0.1);
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
        }

        .toast {
            animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
            }
        }
    </style>
</head>

<body class="bg-[#121212] text-white min-h-screen">

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Header -->
    <header class="h-16 bg-black flex items-center justify-between px-8 sticky top-0 z-40 border-b border-[#222]">
        <div class="flex items-center gap-4">
            <a href="host.php" class="flex items-center gap-2 text-gray-400 hover:text-white transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="text-sm">Back to Host</span>
            </a>
        </div>
        <div class="flex items-center gap-4">
            <img src="images/logo.jpg" alt="logo" class="w-10 h-10 object-contain">
            <div class="text-pink-custom text-xl font-bold">LA ROSE NOIRE</div>
        </div>
        <div class="w-32"></div>
    </header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 py-8">

        <!-- Page Header -->
        <div class="flex items-end gap-6 mb-8">
            <div
                class="w-32 h-32 bg-gradient-to-br from-pink-300 to-pink-600 shadow-2xl flex items-center justify-center text-5xl text-black font-bold rounded-lg">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs uppercase font-bold tracking-wider text-pink-custom">Administration</p>
                <h1 class="text-4xl font-black text-white mb-2">Manage Authorized Users</h1>
                <p id="userCount" class="text-sm text-gray-400">Loading...</p>
            </div>
        </div>

        <!-- Add User Section -->
        <div class="bg-[#1a1a1a] rounded-xl p-6 mb-8 border border-[#333]">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-pink-custom"></i>
                Add User Access
            </h2>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="fa-solid fa-search"></i>
                </div>
                <input type="text" id="searchInput" placeholder="Search by BiometricsID or Name..."
                    class="w-full bg-[#111] border border-[#333] rounded-lg py-3 pl-12 pr-4 text-white focus:outline-none focus:border-pink-custom transition placeholder-gray-500"
                    oninput="handleSearch(this.value)">
            </div>
            <div id="searchResults" class="search-results mt-4 hidden bg-[#111] rounded-lg border border-[#333]"></div>
        </div>

        <!-- Authorized Users Table -->
        <div class="bg-[#1a1a1a] rounded-xl border border-[#333] overflow-hidden">
            <div class="p-6 border-b border-[#333]">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-check text-green-400"></i>
                    Authorized Users
                </h2>
            </div>

            <!-- Table Header -->
            <div
                class="grid grid-cols-[1fr_2fr_100px] gap-4 px-6 py-3 bg-[#111] border-b border-[#333] text-sm uppercase text-gray-500 font-bold">
                <span>BiometricsID</span>
                <span>Name</span>
                <span class="text-right">Actions</span>
            </div>

            <!-- Table Body -->
            <div id="usersList" class="divide-y divide-[#333] custom-scroll max-h-[500px] overflow-y-auto">
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Loading users...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Remove Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50">
        <div class="bg-[#222] p-6 rounded-lg border border-red-500 w-[400px] shadow-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Remove Access</h3>
                    <p class="text-sm text-gray-400">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-gray-300 mb-6">
                Are you sure you want to remove access for <span id="removeUserName"
                    class="font-bold text-pink-custom"></span>?
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 text-gray-400 hover:text-white transition">
                    Cancel
                </button>
                <button onclick="confirmRemove()"
                    class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition">
                    Remove Access
                </button>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout = null;
        let pendingRemoveId = null;
        let pendingRemoveName = '';

        // Load authorized users on page load
        document.addEventListener('DOMContentLoaded', loadUsers);

        async function loadUsers() {
            try {
                const response = await fetch('api_users.php?action=list');
                const data = await response.json();

                if (data.success) {
                    renderUsers(data.users);
                    document.getElementById('userCount').textContent = `${data.users.length} authorized user${data.users.length !== 1 ? 's' : ''}`;
                } else {
                    showError('Failed to load users: ' + data.error);
                }
            } catch (error) {
                showError('Network error: ' + error.message);
            }
        }

        function renderUsers(users) {
            const container = document.getElementById('usersList');

            if (users.length === 0) {
                container.innerHTML = `
                    <div class="px-6 py-12 text-center text-gray-500">
                        <i class="fa-solid fa-user-slash text-4xl mb-3"></i>
                        <p>No authorized users yet</p>
                        <p class="text-sm mt-1">Use the search above to add users</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = users.map(user => {
                const fullName = user.fullname || 'Unknown User';

                return `
                    <div class="grid grid-cols-[1fr_2fr_100px] gap-4 px-6 py-4 user-row items-center">
                        <span class="text-pink-custom font-mono">${user.BiometricsID}</span>
                        <span class="text-white">${fullName}</span>
                        <div class="text-right">
                            <button onclick="showRemoveConfirm('${user.BiometricsID}', '${fullName}')" 
                                class="text-red-400 hover:text-red-300 transition p-2" title="Remove Access">
                                <i class="fa-solid fa-user-minus"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function handleSearch(query) {
            clearTimeout(searchTimeout);
            const resultsDiv = document.getElementById('searchResults');

            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api_users.php?action=search&q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success) {
                        renderSearchResults(data.users);
                    }
                } catch (error) {
                    console.error('Search error:', error);
                }
            }, 300);
        }

        function renderSearchResults(users) {
            const container = document.getElementById('searchResults');

            if (users.length === 0) {
                container.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <i class="fa-solid fa-search text-xl mb-2"></i>
                        <p>No users found</p>
                    </div>
                `;
                container.classList.remove('hidden');
                return;
            }

            container.innerHTML = users.map(user => {
                const fullName = user.fullname || 'Unknown';
                const isAuthorized = user.is_authorized === 1;
                const initial = fullName.charAt(0).toUpperCase();

                return `
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#1a1a1a] transition border-b border-[#222] last:border-b-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#333] rounded-full flex items-center justify-center text-pink-custom font-bold">
                                ${initial}
                            </div>
                            <div>
                                <div class="text-white font-medium">${fullName}</div>
                                <div class="text-xs text-gray-500">${user.BiometricsID}</div>
                            </div>
                        </div>
                        <div>
                            ${isAuthorized
                        ? `<span class="text-green-400 text-sm flex items-center gap-1">
                                    <i class="fa-solid fa-check-circle"></i> Authorized
                                   </span>`
                        : `<button onclick="addUser('${user.BiometricsID}', '${fullName}')" 
                                     class="px-4 py-1.5 bg-pink-custom text-black font-bold text-sm rounded-full hover:scale-105 transition">
                                    <i class="fa-solid fa-plus mr-1"></i> Add
                                   </button>`
                    }
                        </div>
                    </div>
                `;
            }).join('');

            container.classList.remove('hidden');
        }

        async function addUser(biometricsId, name) {
            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('biometrics_id', biometricsId);

                const response = await fetch('api_users.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast(`${name} has been granted access`, 'success');
                    loadUsers();
                    document.getElementById('searchInput').value = '';
                    document.getElementById('searchResults').classList.add('hidden');
                } else {
                    showToast(data.error, 'error');
                }
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
            }
        }

        function showRemoveConfirm(biometricsId, name) {
            pendingRemoveId = biometricsId;
            pendingRemoveName = name;
            document.getElementById('removeUserName').textContent = name;
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
            pendingRemoveId = null;
            pendingRemoveName = '';
        }

        async function confirmRemove() {
            if (!pendingRemoveId) return;

            try {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('biometrics_id', pendingRemoveId);

                const response = await fetch('api_users.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast(`${pendingRemoveName}'s access has been removed`, 'success');
                    loadUsers();
                } else {
                    showToast(data.error, 'error');
                }
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
            }

            closeConfirmModal();
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');

            const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

            toast.className = `toast ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3`;
            toast.innerHTML = `
                <i class="fa-solid ${icon}"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }

        function showError(message) {
            document.getElementById('usersList').innerHTML = `
                <div class="px-6 py-8 text-center text-red-400">
                    <i class="fa-solid fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>

</html>