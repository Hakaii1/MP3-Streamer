<?php // DARK AESTHETIC LOGIN PAGE ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • La Rose Noire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles/login_styles.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        'primary-dark': '#4f46e5',
                        'primary-light': '#818cf8',
                        secondary: '#06b6d4',
                        accent: '#8b5cf6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                        dark: {
                            900: '#0a0a0f',
                            800: '#0f0f1a',
                            700: '#1a1a2e',
                            600: '#252538',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'icon-pulse 3s ease-in-out infinite',
                        'bounce-in': 'bounce-in 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                        'slide-up': 'slide-up 0.6s ease-out',
                        'fade-in': 'fade-in 0.5s ease-out'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Animated Background Grid -->
    <div class="bg-grid"></div>

    <!-- Floating Orbs -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Floating Particles -->
    <div class="bg-particles" id="particles"></div>

    <!-- Cyber Scan Lines -->
    <div class="cyber-lines">
        <div class="cyber-line" style="top: 20%; animation-delay: 0s;"></div>
        <div class="cyber-line" style="top: 50%; animation-delay: 1.5s;"></div>
        <div class="cyber-line" style="top: 80%; animation-delay: 3s;"></div>
    </div>

    <!-- Main Login Container -->
    <div
        class="login-container w-full max-w-5xl glass-panel rounded-3xl overflow-hidden flex flex-col md:flex-row shadow-2xl relative z-10 animate-bounce-in">

        <!-- Left Panel - Brand -->
        <div
            class="brand-panel w-full md:w-5/12 p-12 flex flex-col justify-center items-center relative overflow-hidden">

            <!-- Decorative Corner Elements -->
            <div class="absolute top-4 left-4 w-8 h-8 border-t-2 border-l-2 border-primary/30"></div>
            <div class="absolute top-4 right-4 w-8 h-8 border-t-2 border-r-2 border-primary/30"></div>
            <div class="absolute bottom-4 left-4 w-8 h-8 border-b-2 border-l-2 border-primary/30"></div>
            <div class="absolute bottom-4 right-4 w-8 h-8 border-b-2 border-r-2 border-primary/30"></div>

            <div class="relative z-10 text-center space-y-8">
                <!-- Animated Logo Icon -->
                <div class="brand-icon w-28 h-28 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto">
                    <i class="fas fa-music text-5xl text-primary-light"></i>
                </div>

                <!-- Brand Name -->
                <div class="space-y-4">
                    <h1 class="brand-title text-4xl md:text-5xl font-black bg-gradient-to-r from-primary-light via-accent to-secondary bg-clip-text text-transparent glitch"
                        data-text="La Rose Noire">
                        La Rose Noire
                    </h1>
                    <p class="text-slate-400 text-lg font-medium leading-relaxed tracking-wide">
                        IT Department
                    </p>
                </div>

                <!-- Decorative Lines -->
                <div class="flex items-center justify-center gap-3 pt-4">
                    <div class="w-12 h-0.5 bg-gradient-to-r from-transparent to-primary rounded-full"></div>
                    <div class="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                    <div class="w-12 h-0.5 bg-gradient-to-l from-transparent to-primary rounded-full"></div>
                </div>

            </div>

            <!-- Floating Decorative Elements -->
            <div class="floating-element top-12 right-12 w-16 h-16 rounded-xl flex items-center justify-center"
                style="animation-delay: 1s;">
                <i class="fas fa-headphones text-primary/50 text-xl"></i>
            </div>
            <div class="floating-element bottom-16 left-8 w-12 h-12 rounded-lg flex items-center justify-center"
                style="animation-delay: 3s;">
                <i class="fas fa-volume-up text-accent/50 text-sm"></i>
            </div>
            <div class="floating-element top-1/3 left-6 w-10 h-10 rounded-lg flex items-center justify-center"
                style="animation-delay: 2s;">
                <i class="fas fa-compact-disc text-secondary/50 text-xs"></i>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="form-panel w-full md:w-7/12 p-12">
            <div class="space-y-8">
                <!-- Header -->
                <div class="text-center space-y-4 animate-slide-up" style="animation-delay: 0.2s;">
                    <h2
                        class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-slate-100 to-slate-300 bg-clip-text text-transparent">
                        Welcome Back
                    </h2>
                    <p class="text-slate-500 text-lg leading-relaxed">
                        Use your company credentials to sign in
                    </p>
                    <div class="w-20 h-1 bg-gradient-to-r from-primary via-accent to-secondary rounded-full mx-auto">
                    </div>
                </div>

                <!-- Login Form -->
                <form action="auth/authenticate.php" method="POST" class="space-y-6">
                    <!-- Username Field -->
                    <div class="space-y-2 animate-slide-up" style="animation-delay: 0.3s;">
                        <label class="block text-sm font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fas fa-user text-primary"></i>
                            Username
                        </label>
                        <div class="relative group">
                            <input type="text" name="username" required
                                class="form-input pl-14 pr-4 py-4 text-slate-200 placeholder-slate-600"
                                placeholder="Enter your username">
                            <div class="input-icon-wrapper">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                            <div
                                class="absolute inset-0 rounded-xl bg-gradient-to-r from-primary/0 via-primary/5 to-primary/0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2 animate-slide-up" style="animation-delay: 0.4s;">
                        <label class="block text-sm font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fas fa-lock text-primary"></i>
                            Password
                        </label>
                        <div class="relative group">
                            <input type="password" name="password" required
                                class="form-input pl-14 pr-14 py-4 text-slate-200 placeholder-slate-600"
                                placeholder="••••••••" id="password">
                            <div class="input-icon-wrapper">
                                <i class="fas fa-lock text-white text-xs"></i>
                            </div>
                            <button type="button" onclick="togglePassword()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-primary transition-colors p-2 rounded-lg hover:bg-primary/10">
                                <i class="fas fa-eye text-sm" id="password-toggle"></i>
                            </button>
                            <div
                                class="absolute inset-0 rounded-xl bg-gradient-to-r from-primary/0 via-primary/5 to-primary/0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            </div>
                        </div>

                        <!-- Forgot Password Link -->
                        <div class="flex justify-end">
                            <button type="button" id="forgot-link"
                                class="text-sm font-semibold text-primary-light hover:text-primary transition-all duration-300 flex items-center gap-2 group">
                                <i class="fas fa-key text-xs group-hover:rotate-12 transition-transform"></i>
                                Forgot Password?
                            </button>
                        </div>
                    </div>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid'): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle mr-2"></i> Invalid username or password.
                        </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4 animate-slide-up" style="animation-delay: 0.5s;">
                        <button type="submit"
                            class="btn-primary w-full md:w-auto px-12 py-4 text-base font-semibold group">
                            <span>Sign In</span>
                            <i
                                class="fas fa-arrow-right group-hover:translate-x-2 transition-transform duration-300"></i>
                        </button>
                    </div>
                </form>

                <!-- Security Footer -->
                <div class="pt-6 border-t border-slate-800/50 text-center animate-fade-in"
                    style="animation-delay: 0.6s;">
                    <p class="text-slate-600 text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-shield-alt text-primary-light"></i>
                        Secure access to your workspace
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-primary/20 to-accent/20 rounded-xl flex items-center justify-center border border-primary/30">
                        <i class="fas fa-key text-2xl text-primary-light"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-100">Reset Password</h3>
                        <p class="text-sm text-slate-500">Password recovery</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="text-slate-400 mb-6 leading-relaxed">
                    For security reasons, please contact the IT department directly to reset your credentials.
                </p>
                <button class="btn-secondary" onclick="closeModal()">
                    <i class="fas fa-check mr-2"></i>Understood
                </button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-red-500/20 to-orange-500/20 rounded-xl flex items-center justify-center border border-red-500/30">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-100">Authentication Error</h3>
                        <p class="text-sm text-slate-500">Login failed</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeErrorModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="text-slate-400 mb-6 leading-relaxed">
                    Invalid Username or Password. Please check your credentials and try again.
                </p>
                <button class="btn-primary" onclick="closeErrorModal()">
                    <i class="fas fa-redo mr-2"></i>Try Again
                </button>
            </div>
        </div>
    </div>

    <!-- Unauthorized Modal -->
    <div id="unauthorized-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-red-500/20 to-red-500/20 rounded-xl flex items-center justify-center border border-red-500/30">
                        <i class="fas fa-ban text-2xl text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-100">Access Restricted</h3>
                        <p class="text-sm text-slate-500">Unauthorized access</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeUnauthorizedModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="text-slate-400 mb-4 leading-relaxed">
                    Your account is not authorized to access the Host Panel.
                </p>
                <p class="text-slate-500 text-sm mb-6">
                    <i class="fas fa-info-circle mr-1"></i>
                    Contact IT Department for access requests.
                </p>

            </div>
        </div>
    </div>

    <script>
        // Generate Floating Particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (6 + Math.random() * 4) + 's';

                // Random colors
                const colors = ['#6366f1', '#8b5cf6', '#06b6d4', '#a78bfa'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];

                container.appendChild(particle);
            }
        }

        createParticles();

        // Password Toggle
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('password-toggle');
            if (password.type === 'password') {
                password.type = 'text';
                toggle.className = 'fas fa-eye-slash text-sm';
            } else {
                password.type = 'password';
                toggle.className = 'fas fa-eye text-sm';
            }
        }

        // Modal Functions
        function openModal() {
            document.getElementById('forgot-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('forgot-modal').classList.remove('active');
        }

        function openErrorModal() {
            document.getElementById('error-modal').classList.add('active');
        }

        function closeErrorModal() {
            document.getElementById('error-modal').classList.remove('active');
            clearErrorParam();
        }

        function openUnauthorizedModal() {
            document.getElementById('unauthorized-modal').classList.add('active');
        }

        function closeUnauthorizedModal() {
            document.getElementById('unauthorized-modal').classList.remove('active');
            clearErrorParam();
        }

        function clearErrorParam() {
            // Remove error parameter from URL without reloading
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url);
        }

        // Event Listeners
        document.getElementById('forgot-link').addEventListener('click', openModal);

        // Keyboard Navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
                closeErrorModal();
                closeUnauthorizedModal();
            }
        });

        // Close modal when clicking overlay
        document.getElementById('forgot-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('error-modal').addEventListener('click', function (e) {
            if (e.target === this) closeErrorModal();
        });

        document.getElementById('unauthorized-modal').addEventListener('click', function (e) {
            if (e.target === this) closeUnauthorizedModal();
        });

        // Check for error parameter in URL and show modal
        window.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const errorType = urlParams.get('error');
            if (errorType === 'invalid') {
                openErrorModal();
            } else if (errorType === 'unauthorized') {
                openUnauthorizedModal();
            }
        });

        // Add subtle mouse movement effect on the container
        const loginContainer = document.querySelector('.login-container');
        document.addEventListener('mousemove', (e) => {
            const { clientX, clientY } = e;
            const { innerWidth, innerHeight } = window;

            const xPercent = (clientX / innerWidth - 0.5) * 2;
            const yPercent = (clientY / innerHeight - 0.5) * 2;

            loginContainer.style.transform = `perspective(1000px) rotateY(${xPercent * 2}deg) rotateX(${-yPercent * 2}deg)`;
        });

        loginContainer.addEventListener('mouseleave', () => {
            loginContainer.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
        });
    </script>
</body>

</html>