<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Teach.me') }} &mdash; The smarter way to actually learn</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased selection:bg-purple-500/30">

        {{-- Navbar --}}
        <nav class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur border-b border-white/5">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm">t</span>
                    <span class="text-lg font-semibold tracking-tight">teach<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">.me</span></span>
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#stats" class="hover:text-white transition">Explore</a>
                    <a href="#adaptive" class="hover:text-white transition">Courses</a>
                    <a href="#stories" class="hover:text-white transition">Mentors</a>
                    <a href="#cta" class="hover:text-white transition">Pricing</a>
                </div>

                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg hover:opacity-90 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg hover:opacity-90 transition shadow-lg shadow-purple-500/20">
                                    Start for free
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        {{-- Hero --}}
        <section class="relative overflow-hidden px-4 pt-20 pb-28 sm:pt-28 sm:pb-36 text-center">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_60%_50%_at_50%_0%,theme(colors.purple.900/40),transparent)]"></div>

            <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-semibold tracking-widest uppercase rounded-full bg-white/5 border border-white/10 text-purple-300">
                Your Learning. Elevated.
            </span>

            <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight max-w-3xl mx-auto">
                The smarter way to
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-purple-400">actually learn.</span>
            </h1>

            <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-xl mx-auto leading-relaxed">
                Assignments, grading, and progress tracking &mdash; built into one focused classroom experience for teachers and students.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="px-7 py-3 text-base font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl shadow-lg shadow-purple-500/25 hover:opacity-90 transition">
                        Start for free
                    </a>
                @endif
                <a href="#adaptive"
                   class="px-7 py-3 text-base font-medium text-slate-200 hover:text-white transition">
                    Browse courses &rarr;
                </a>
            </div>

            <div class="mt-16 flex flex-col items-center gap-2 text-xs font-medium tracking-widest uppercase text-slate-500">
                <span>Scroll</span>
                <span class="w-px h-8 bg-gradient-to-b from-slate-500 to-transparent"></span>
            </div>
        </section>

        {{-- Stats --}}
        <section id="stats" class="border-y border-white/5 bg-white/[0.02] py-14 px-4" x-data="statsCounter()" x-init="init()">
            <div class="max-w-5xl mx-auto grid grid-cols-2 sm:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400" x-text="display.courses + '+'"></div>
                    <div class="mt-1 text-xs sm:text-sm font-medium text-slate-400 uppercase tracking-wide">Courses</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400" x-text="display.learners + 'K+'"></div>
                    <div class="mt-1 text-xs sm:text-sm font-medium text-slate-400 uppercase tracking-wide">Learners</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400" x-text="display.passRate + '%'"></div>
                    <div class="mt-1 text-xs sm:text-sm font-medium text-slate-400 uppercase tracking-wide">Pass rate</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400" x-text="display.countries + '+'"></div>
                    <div class="mt-1 text-xs sm:text-sm font-medium text-slate-400 uppercase tracking-wide">Countries</div>
                </div>
            </div>
        </section>

        {{-- Adaptive Learning --}}
        <section id="adaptive" class="px-4 py-24 sm:py-32">
            <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="text-xs font-semibold tracking-widest uppercase text-purple-300">Adaptive Learning</span>
                    <h2 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight">See your progress, in real time.</h2>
                    <p class="mt-5 text-slate-400 leading-relaxed">
                        Every lesson, quiz, and assignment feeds into a live progress picture &mdash; so students always know exactly where they stand, and teachers always know who needs support.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-2xl"
                     x-data="progressCard()" x-init="init()">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="text-sm font-semibold text-white">Calculus</div>
                            <div class="text-xs text-slate-500">5 topics in progress</div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-400/30 text-purple-200 text-xs font-semibold">
                            🔥 14-day streak
                        </span>
                    </div>

                    <div class="space-y-4">
                        <template x-for="topic in topics" :key="topic.name">
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="text-slate-300" x-text="topic.name"></span>
                                    <span class="text-slate-400 font-medium" x-text="topic.width + '%'"></span>
                                </div>
                                <div class="h-2 rounded-full bg-white/5 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-1000 ease-out"
                                         :style="`width: ${topic.animated ? topic.width : 0}%`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        {{-- Student Stories --}}
        <section id="stories" class="px-4 py-24 sm:py-32 border-t border-white/5 bg-white/[0.02]"
                 x-data="testimonialCarousel()" x-init="init()">
            <div class="max-w-3xl mx-auto text-center">
                <span class="text-xs font-semibold tracking-widest uppercase text-purple-300">Student Stories</span>
                <h2 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight">Loved by learners everywhere.</h2>

                <div class="relative mt-12 min-h-[180px]">
                    <template x-for="(story, index) in stories" :key="story.name">
                        <div x-show="active === index"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            <p class="text-xl sm:text-2xl font-medium text-slate-100 leading-relaxed">&ldquo;<span x-text="story.quote"></span>&rdquo;</p>
                            <div class="mt-6">
                                <div class="font-semibold text-white" x-text="story.name"></div>
                                <div class="text-sm text-slate-400" x-text="story.role"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-10 flex items-center justify-center gap-2">
                    <template x-for="(story, index) in stories" :key="index">
                        <button @click="setActive(index)"
                                class="h-2 rounded-full transition-all"
                                :class="active === index ? 'w-6 bg-gradient-to-r from-purple-400 to-pink-400' : 'w-2 bg-white/15 hover:bg-white/30'">
                        </button>
                    </template>
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section id="cta" class="relative overflow-hidden px-4 py-24 sm:py-32 text-center">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_60%_60%_at_50%_50%,theme(colors.purple.900/30),transparent)]"></div>

            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight max-w-2xl mx-auto">Your next chapter starts here.</h2>
            <p class="mt-5 text-slate-400 max-w-lg mx-auto">Join thousands of students and teachers already learning smarter with teach.me.</p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="px-7 py-3 text-base font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl shadow-lg shadow-purple-500/25 hover:opacity-90 transition">
                        Get started free
                    </a>
                @endif
                <a href="#adaptive"
                   class="px-7 py-3 text-base font-medium text-slate-200 hover:text-white transition">
                    Browse courses
                </a>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-white/5 px-4 py-14">
            <div class="max-w-6xl mx-auto grid sm:grid-cols-2 md:grid-cols-4 gap-10">
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-xs">t</span>
                        <span class="font-semibold">teach<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">.me</span></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500 leading-relaxed">The smarter way to actually learn &mdash; assignments, grading, and progress in one place.</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-white mb-3">Learn</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#adaptive" class="hover:text-white transition">Courses</a></li>
                        <li><a href="#stats" class="hover:text-white transition">Progress tracking</a></li>
                        <li><a href="#stories" class="hover:text-white transition">Student stories</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-white mb-3">Company</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-white transition">About</a></li>
                        <li><a href="#" class="hover:text-white transition">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-white mb-3">Support</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-white transition">Help center</a></li>
                        @if (Route::has('login'))
                            <li><a href="{{ route('login') }}" class="hover:text-white transition">Sign in</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="max-w-6xl mx-auto mt-12 pt-6 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <span>&copy; {{ date('Y') }} teach.me. All rights reserved.</span>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-white transition">Terms</a>
                    <a href="#" class="hover:text-white transition">Privacy</a>
                    <a href="#" class="hover:text-white transition">Cookies</a>
                </div>
            </div>
        </footer>

        @if (! (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))))
            <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        @endif

        <script>
            function statsCounter() {
                return {
                    display: { courses: 0, learners: 0, passRate: 0, countries: 0 },
                    targets: { courses: 120, learners: 45, passRate: 98, countries: 30 },
                    started: false,
                    init() {
                        const el = this.$el;
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting && !this.started) {
                                    this.started = true;
                                    this.animate();
                                }
                            });
                        }, { threshold: 0.4 });
                        observer.observe(el);
                    },
                    animate() {
                        const duration = 1400;
                        const start = performance.now();
                        const step = (now) => {
                            const progress = Math.min((now - start) / duration, 1);
                            const eased = 1 - Math.pow(1 - progress, 3);
                            Object.keys(this.targets).forEach((key) => {
                                this.display[key] = Math.round(this.targets[key] * eased);
                            });
                            if (progress < 1) requestAnimationFrame(step);
                        };
                        requestAnimationFrame(step);
                    },
                };
            }

            function progressCard() {
                return {
                    topics: [
                        { name: 'Limits & Continuity', width: 100, animated: false },
                        { name: 'Derivatives', width: 88, animated: false },
                        { name: 'Chain Rule', width: 72, animated: false },
                        { name: 'Integration', width: 41, animated: false },
                        { name: 'Multivariable', width: 18, animated: false },
                    ],
                    init() {
                        const el = this.$el;
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    this.topics.forEach((topic) => (topic.animated = true));
                                }
                            });
                        }, { threshold: 0.3 });
                        observer.observe(el);
                    },
                };
            }

            function testimonialCarousel() {
                return {
                    active: 0,
                    interval: null,
                    stories: [
                        { quote: 'I finally understand calculus. The progress tracker keeps me accountable every single day.', name: 'Amara O.', role: 'Grade 12 Student, Lagos' },
                        { quote: 'Grading used to take me all weekend. Now I give better feedback in half the time.', name: 'Mr. Chen', role: 'Mathematics Teacher, Singapore' },
                        { quote: 'The streaks and progress bars actually made me want to keep studying. It just clicked.', name: 'Sofia R.', role: 'Grade 10 Student, Madrid' },
                    ],
                    init() {
                        this.interval = setInterval(() => this.next(), 5000);
                    },
                    next() {
                        this.active = (this.active + 1) % this.stories.length;
                    },
                    setActive(index) {
                        this.active = index;
                        clearInterval(this.interval);
                        this.interval = setInterval(() => this.next(), 5000);
                    },
                };
            }
        </script>
    </body>
</html>